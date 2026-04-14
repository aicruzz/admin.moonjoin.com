<?php

namespace App\Http\Controllers\Vendor;



use App\Models\Admin;
use App\Models\Store;
use App\Library\Payer;
use App\Traits\Payment;
use App\Library\Receiver;
use App\Models\StoreWallet;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\WithdrawRequest;
use App\Models\WithdrawalMethod;
use App\Mail\WithdrawRequestMail;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DisbursementDetails;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Library\Payment as PaymentInfo;
use Illuminate\Support\Facades\Validator;
use App\Exports\DisbursementHistoryExport;
use Modules\Rental\Emails\ProviderWithdrawRequestMail;

class WalletController extends Controller
{
    public function index()
    {
        $data = data_get($this->getWithdrawMethods(), 'data', []);
        $withdrawal_methods = data_get($this->getWithdrawMethods(), 'withdrawal_methods', []);
        $withdraw_req = WithdrawRequest::with(['vendor', 'method'])->where('vendor_id', Helpers::get_vendor_id())->latest()->paginate(config('default_pagination'));
        return view('vendor-views.wallet.index', compact('withdraw_req', 'withdrawal_methods', 'data'));
    }
    public function w_request(Request $request)
    {
        // =========================================================================
        // Step 1 — Validate inputs
        // =========================================================================
        \Log::info('9PSB VENDOR | STEP 1 - REQUEST HIT', $request->all());

        $validator = Validator::make($request->all(), [
            'withdraw_method' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'account_number' => 'required|string|size:10',
            'bank_code' => 'nullable|string',
            'account_name' => 'required|string|max:255',
            'narration' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            \Log::error('9PSB VENDOR | STEP 1 FAILED - VALIDATION', [
                'errors' => $validator->errors()->toArray(),
            ]);
            Toastr::error($validator->errors()->first());
            return redirect()->back();
        }

        \Log::info('9PSB VENDOR | STEP 1 PASSED - VALIDATION OK');

        // =========================================================================
        // Step 2 — Load withdrawal method + build method_data
        // =========================================================================
        $method = WithdrawalMethod::find($request->withdraw_method);

        if (!$method) {
            \Log::error('9PSB VENDOR | STEP 2 FAILED - Method not found', [
                'withdraw_method' => $request->withdraw_method,
            ]);
            Toastr::error('Invalid withdrawal method selected.');
            return redirect()->back();
        }

        $fields = array_column($method->method_fields, 'input_name');
        $method_data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $method_data[$field] = $request->input($field);
            }
        }

        \Log::info('9PSB VENDOR | STEP 2 - METHOD AND DATA', [
            'method_id' => $method->id,
            'method_name' => $method->method_name,
            'method_data' => $method_data,
        ]);

        // =========================================================================
        // Step 3 — Resolve bank code from hardcoded map
        // =========================================================================
        $method_name = strtolower(trim($method->method_name ?? ''));
        $bank_code_from_form = $method_data['bank_code'] ?? $request->bank_code ?? null;

        $bankCodeMap = [
            'opay' => '100004',
            'palmpay' => '100033',
            'airpero' => '090133',
            'bank transfer' => $bank_code_from_form ?? '100004',
        ];

        $bank_code = '100004'; // fallback
        foreach ($bankCodeMap as $keyword => $code) {
            if (str_contains($method_name, $keyword)) {
                $bank_code = $code;
                break;
            }
        }

        $account_number = $method_data['account_number'] ?? $request->account_number ?? null;

        \Log::info('9PSB VENDOR | STEP 3 - BANK CODE RESOLVED', [
            'method_name' => $method_name,
            'bank_code' => $bank_code,
            'account_number' => $account_number,
        ]);

        // =========================================================================
        // Step 4 — Balance check
        // =========================================================================
        $w = StoreWallet::where('vendor_id', Helpers::get_vendor_id())->first();

        if (!$w) {
            \Log::error('9PSB VENDOR | STEP 4 FAILED - Wallet not found', [
                'vendor_id' => Helpers::get_vendor_id(),
            ]);
            Toastr::error('Wallet not found.');
            return redirect()->back();
        }

        \Log::info('9PSB VENDOR | STEP 4 - WALLET CHECK', [
            'wallet_balance' => $w->balance,
            'requested_amount' => $request->amount,
            'has_enough' => $w->balance >= $request->amount ? 'YES' : 'NO',
        ]);

        if ($w->balance < $request->amount) {
            \Log::warning('9PSB VENDOR | STEP 4 FAILED - Insufficient balance', [
                'wallet_balance' => $w->balance,
                'requested_amount' => $request->amount,
            ]);
            Toastr::error('Insufficient balance.');
            return redirect()->back();
        }

        // =========================================================================
        // Step 5 — Save withdraw request inside lock
        // =========================================================================
        try {
            $withdraw = DB::transaction(function () use ($w, $request, $method_data) {

                $wallet = StoreWallet::where('vendor_id', $w->vendor_id)
                    ->lockForUpdate()
                    ->first();

                // Idempotency — reuse pending request created in last 2 mins
                $existing = WithdrawRequest::where('vendor_id', $wallet->vendor_id)
                    ->where('amount', $request->amount)
                    ->where('approved', 0)
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->first();

                if ($existing) {
                    \Log::warning('9PSB VENDOR | STEP 5 - IDEMPOTENCY reusing existing request', [
                        'withdraw_id' => $existing->id,
                    ]);
                    return $existing;
                }

                // Re-check balance inside lock
                if ($wallet->balance < $request->amount) {
                    throw new \Exception('insufficient_balance');
                }

                DB::table('withdraw_requests')->insert([
                    'vendor_id' => $wallet->vendor_id,
                    'amount' => $request->amount,
                    'transaction_note' => null,
                    'withdrawal_method_id' => $request->withdraw_method,
                    'withdrawal_method_fields' => json_encode($method_data),
                    'approved' => 0,  // pending until payout confirmed
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return WithdrawRequest::where('vendor_id', $wallet->vendor_id)
                    ->latest()
                    ->first();
            });

            \Log::info('9PSB VENDOR | STEP 5 - WITHDRAW SAVED', [
                'withdraw_id' => $withdraw->id,
                'amount' => $withdraw->amount,
                'approved' => $withdraw->approved,
            ]);

        } catch (\Exception $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                Toastr::error('Insufficient balance.');
                return redirect()->back();
            }

            \Log::error('9PSB VENDOR | STEP 5 FAILED - DB error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            Toastr::error('Could not save withdrawal request. Please try again.');
            return redirect()->back();
        }

        // =========================================================================
        // Step 6 — 9PSB auto-payout (enquiry → payout → approve)
        // =========================================================================
        if (!$account_number || !$bank_code) {
            \Log::warning('9PSB VENDOR | STEP 6 SKIPPED - Missing account number or bank code', [
                'account_number' => $account_number ?? 'NULL',
                'bank_code' => $bank_code ?? 'NULL',
            ]);
            goto send_notification;
        }

        try {
            $ninePsb = new \App\Http\Controllers\Api\V1\NinePsbPaymentController();

            // -----------------------------------------------------------------
            // Step 7 — Get bank list and match bank code
            // -----------------------------------------------------------------
            $banksRaw = json_decode($ninePsb->getBanks()->getContent(), true);

            \Log::info('9PSB VENDOR | STEP 7 - GET BANKS RESPONSE', [
                'success' => $banksRaw['success'] ?? 'NULL',
                'data_keys' => array_keys($banksRaw['data'] ?? []),
            ]);

            $bankList = $banksRaw['data']['data']['bankList']
                ?? $banksRaw['data']['bankList']
                ?? $banksRaw['data']['data']['data']['bankList']
                ?? [];

            \Log::info('9PSB VENDOR | STEP 7B - BANK LIST COUNT', [
                'count' => count($bankList),
            ]);

            $matchedBank = collect($bankList)->firstWhere('bankCode', $bank_code);

            \Log::info('9PSB VENDOR | STEP 7C - BANK MATCH', [
                'bank_code' => $bank_code,
                'matched_bank' => $matchedBank ?? 'NOT FOUND',
            ]);

            if (!$matchedBank) {
                \Log::error('9PSB VENDOR | STEP 7 FAILED - Bank code not in list', [
                    'bank_code' => $bank_code,
                    'method_name' => $method_name,
                ]);
                goto send_notification;
            }

            $bank_name = $matchedBank['bankName'];

            // -----------------------------------------------------------------
            // Step 8 — Name enquiry
            // -----------------------------------------------------------------
            $enquiryRequest = new \Illuminate\Http\Request();
            $enquiryRequest->replace([
                'accountNumber' => $account_number,
                'bankCode' => $bank_code,
            ]);

            $enquiryResponse = json_decode(
                $ninePsb->nameEnquiry($enquiryRequest)->getContent(),
                true
            );

            \Log::info('9PSB VENDOR | STEP 8 - NAME ENQUIRY RESPONSE', $enquiryResponse ?? []);

            $account_name = $enquiryResponse['data']['customer']['account']['name']
                ?? $enquiryResponse['raw']['data']['customer']['account']['name']
                ?? $enquiryResponse['data']['accountName']
                ?? $enquiryResponse['data']['account_name']
                ?? null;

            if (!$account_name) {
                \Log::error('9PSB VENDOR | STEP 8 FAILED - Account name not resolved', [
                    'raw' => $enquiryResponse,
                ]);
                goto send_notification;
            }

            \Log::info('9PSB VENDOR | STEP 8 SUCCESS - Account name resolved', [
                'account_name' => $account_name,
                'bank_name' => $bank_name,
            ]);

            // -----------------------------------------------------------------
            // Step 9 — Fire payout (with duplicate guard)
            // -----------------------------------------------------------------
            $freshWithdraw = WithdrawRequest::find($withdraw->id);

            if ($freshWithdraw->approved == 1) {
                \Log::warning('9PSB VENDOR | STEP 9 SKIPPED - Already approved, duplicate payout prevented', [
                    'withdraw_id' => $withdraw->id,
                ]);
                goto send_notification;
            }

            // Step 9 — Fire payout (with duplicate guard)
            DB::transaction(function () use ($freshWithdraw, $ninePsb, $account_number, $bank_code, $account_name, $bank_name, $w, &$withdraw) {

                $locked = WithdrawRequest::where('id', $freshWithdraw->id)
                    ->where('approved', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$locked) {
                    \Log::warning('9PSB VENDOR | STEP 9 SKIPPED INSIDE LOCK - Already approved by another process', [
                        'withdraw_id' => $freshWithdraw->id,
                    ]);
                    return;
                }

                $payoutRequest = new \Illuminate\Http\Request();
                $payoutRequest->replace([
                    'amount' => $locked->amount,
                    'accountNumber' => $account_number,
                    'bankCode' => $bank_code,
                    'accountName' => $account_name,
                    'narration' => 'Vendor Withdraw ID: ' . $locked->id,  // satisfies required|string
                ]);

                $payoutResponse = json_decode(
                    $ninePsb->payoutStoreToBank($payoutRequest)->getContent(),  // ← correct method
                    true
                );

                \Log::info('9PSB VENDOR | STEP 9 - PAYOUT RESPONSE', $payoutResponse ?? []);

                if (!isset($payoutResponse['success']) || $payoutResponse['success'] !== true) {
                    \Log::error('9PSB VENDOR | STEP 9 FAILED - Payout unsuccessful', [
                        'message' => $payoutResponse['message'] ?? 'Unknown',
                        'raw' => $payoutResponse,
                    ]);
                    return;
                }

                // Step 10 — Auto-approve + deduct balance
                $locked->approved = 1;
                $locked->transaction_note = 'Auto-paid via 9PSB to '
                    . $account_name
                    . ' (' . $bank_name . ') on '
                    . now()->toDateTimeString();
                $locked->save();

                $w->decrement('balance', $locked->amount);

                \Log::info('9PSB VENDOR | STEP 10 SUCCESS - AUTO APPROVED + BALANCE DEDUCTED', [
                    'withdraw_id' => $locked->id,
                    'account_name' => $account_name,
                    'bank_name' => $bank_name,
                    'amount' => $locked->amount,
                ]);

                $withdraw = $locked;
            });

        } catch (\Exception $e) {
            \Log::error('9PSB VENDOR | 9PSB EXCEPTION', [
                'withdraw_id' => $withdraw->id ?? 'NULL',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }

        // =========================================================================
        // Step 11 — Send notification email
        // =========================================================================
        send_notification:

        \Log::info('9PSB VENDOR | STEP 11 - SENDING NOTIFICATION EMAIL', [
            'vendor_id' => Helpers::get_vendor_id(),
            'withdraw_id' => $withdraw->id ?? 'NULL',
        ]);

        try {
            $admin = Admin::where('role_id', 1)->first();

            $wallet_transaction = WithdrawRequest::where('vendor_id', Helpers::get_vendor_id())
                ->latest()
                ->first();

            if (
                Helpers::get_store_data()?->module?->module_type !== 'rental' &&
                config('mail.status') &&
                Helpers::get_mail_status('withdraw_request_mail_status_admin') == '1' &&
                Helpers::getNotificationStatusData('admin', 'withdraw_request', 'mail_status')
            ) {
                Mail::to($admin->email)->send(new WithdrawRequestMail('admin_mail', $wallet_transaction));
                \Log::info('9PSB VENDOR | STEP 11 - Admin withdraw mail sent', [
                    'admin_email' => $admin->email,
                ]);

            } elseif (
                Helpers::get_store_data()?->module?->module_type == 'rental' &&
                addon_published_status('Rental') &&
                config('mail.status') &&
                Helpers::get_mail_status('rental_withdraw_request_mail_status_admin') == '1' &&
                Helpers::getRentalNotificationStatusData('admin', 'provider_withdraw_request', 'mail_status')
            ) {
                Mail::to($admin->email)->send(new ProviderWithdrawRequestMail('pending', $wallet_transaction));
                \Log::info('9PSB VENDOR | STEP 11 - Rental provider withdraw mail sent', [
                    'admin_email' => $admin->email,
                ]);
            } else {
                \Log::info('9PSB VENDOR | STEP 11 - Email skipped (conditions not met)', [
                    'mail_status' => config('mail.status'),
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('9PSB VENDOR | STEP 11 FAILED - Mail error', [
                'error' => $e->getMessage(),
            ]);
        }

        \Log::info('9PSB VENDOR | COMPLETED', [
            'vendor_id' => Helpers::get_vendor_id(),
            'withdraw_id' => $withdraw->id ?? 'NULL',
            'approved' => $withdraw->approved ?? 'NULL',
            'amount' => $withdraw->amount ?? 'NULL',
        ]);

        Toastr::success('Withdrawal processed successfully.');
        return redirect()->back();
    }

    public function close_request($id)
    {
        $wr = WithdrawRequest::find($id);
        if ($wr->approved == 0) {
            StoreWallet::where('vendor_id', Helpers::get_vendor_id())->decrement('pending_withdraw', $wr['amount']);
        }
        $wr->delete();
        Toastr::success('request closed!');
        return back();
    }


    public function method_list(Request $request)
    {
        $method = WithdrawalMethod::ofStatus(1)->where('id', $request->method_id)->first();

        return response()->json(['content' => $method], 200);
    }


    public function make_wallet_adjustment()
    {
        $wallet = StoreWallet::firstOrNew(
            ['vendor_id' => Helpers::get_vendor_id()]
        );

        $wallet_earning = round($wallet->total_earning - ($wallet->total_withdrawn + $wallet->pending_withdraw), 8);
        $adj_amount = round($wallet->collected_cash - $wallet_earning, 8);

        if ($wallet->collected_cash == 0 || $wallet_earning == 0 || ($wallet_earning == $wallet->balance)) {
            Toastr::info(translate('Already_Adjusted'));
            return back();
        }

        if ($adj_amount > 0) {
            $wallet->total_withdrawn = $wallet->total_withdrawn + $wallet_earning;
            $wallet->collected_cash = $wallet->collected_cash - $wallet_earning;

            $data = [
                'vendor_id' => Helpers::get_vendor_id(),
                'amount' => $wallet_earning,
                'transaction_note' => "Store_wallet_adjustment_partial",
                'withdrawal_method_id' => null,
                'withdrawal_method_fields' => null,
                'approved' => 1,
                'type' => 'adjustment',
                'created_at' => now(),
                'updated_at' => now()
            ];

        } else {

            $data = [
                'vendor_id' => Helpers::get_vendor_id(),
                'amount' => $wallet->collected_cash,
                'transaction_note' => "Store_wallet_adjustment_full",
                'withdrawal_method_id' => null,
                'withdrawal_method_fields' => null,
                'approved' => 1,
                'type' => 'adjustment',
                'created_at' => now(),
                'updated_at' => now()
            ];
            $wallet->total_withdrawn = $wallet->total_withdrawn + $wallet->collected_cash;
            $wallet->collected_cash = 0;

        }

        $wallet->save();
        DB::table('withdraw_requests')->insert($data);
        Toastr::success(translate('store_wallet_adjustment_successfull'));
        return back();
    }

    public function make_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
            'payment_gateway' => 'required',
            'amount' => 'required|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $store = Store::findOrfail($request->store_id);

        $payer = new Payer(
            $store->name,
            $store->email,
            $store->phone,
            ''
        );
        $store_logo = BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
            'business_logo' => \App\CentralLogics\Helpers::get_full_url('business', $store_logo?->value, $store_logo?->storage[0]?->value ?? 'public')
        ];
        $payment_info = new PaymentInfo(
            success_hook: 'collect_cash_success',
            failure_hook: 'collect_cash_fail',
            currency_code: Helpers::currency_code(),
            payment_method: $request->payment_gateway,
            payment_platform: 'web',
            payer_id: $store->vendor->id,
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $request->amount,
            external_redirect_link: route('vendor.wallet.index'),
            attribute: 'store_collect_cash_payments',
            attribute_id: $store->vendor->id,
        );

        $receiver_info = new Receiver('Admin', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return redirect($redirect_link);

    }

    public function wallet_payment_list(Request $request)
    {

        $data = data_get($this->getWithdrawMethods(), 'data', []);
        $withdrawal_methods = data_get($this->getWithdrawMethods(), 'withdrawal_methods', []);

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $account_transaction = AccountTransaction::
            when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('ref', 'like', "%{$value}%");
                    }
                });
            })
            ->where('type', 'collected')
            ->where('created_by', 'store')
            ->where('from_id', Helpers::get_vendor_id())
            ->where('from_type', 'store')
            ->latest()->paginate(config('default_pagination'));
        return view('vendor-views.wallet.payment_list', compact('account_transaction', 'withdrawal_methods', 'data'));
    }
    public function getDisbursementList(Request $request)
    {

        $data = data_get($this->getWithdrawMethods(), 'data', []);
        $withdrawal_methods = data_get($this->getWithdrawMethods(), 'withdrawal_methods', []);

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $disbursements = DisbursementDetails::with('store', 'withdraw_method')
            ->where('store_id', Helpers::get_store_id())
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('disbursement_id', 'like', "%{$value}%")
                            ->orWhere('status', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->paginate(config('default_pagination'));
        return view('vendor-views.wallet.disbursement', compact('disbursements', 'withdrawal_methods', 'data'));
    }
    private function getWithdrawMethods()
    {
        $withdrawal_methods = WithdrawalMethod::ofStatus(1)->get();

        $published_status = 0;
        $payment_published_status = config('get_payment_publish_status');
        if (isset($payment_published_status[0]['is_published'])) {
            $published_status = $payment_published_status[0]['is_published'];
        }

        $methods = DB::table('addon_settings')->where('is_active', 1)->where('settings_type', 'payment_config')

            ->when($published_status == 0, function ($q) {
                $q->whereIn('key_name', ['ssl_commerz', 'paypal', 'stripe', 'razor_pay', 'senang_pay', 'paytabs', 'paystack', 'paymob_accept', 'paytm', 'flutterwave', 'liqpay', 'bkash', 'mercadopago']);
            })
            ->get();
        $env = env('APP_ENV') == 'live' ? 'live' : 'test';
        $credentials = $env . '_values';

        $data = [];
        foreach ($methods as $method) {
            $credentialsData = json_decode($method->$credentials);
            $additional_data = json_decode($method->additional_data);
            if ($credentialsData->status == 1) {
                $data[] = [
                    'gateway' => $method->key_name,
                    'gateway_title' => $additional_data?->gateway_title,
                    'gateway_image' => $additional_data?->gateway_image
                ];
            }
        }

        $result = [
            'data' => $data,
            'withdrawal_methods' => $withdrawal_methods,
        ];

        return $result;
    }
    public function getDisbursementExport(Request $request)
    {

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $disbursements = DisbursementDetails::with('store', 'withdraw_method')
            ->where('store_id', Helpers::get_store_id())
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('disbursement_id', 'like', "%{$value}%")
                            ->orWhere('status', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->get();

        $data = [
            'disbursements' => $disbursements,
            'search' => $request->search ?? null,
            'store' => Helpers::get_store_data()->name,
            'type' => 'store',
        ];

        if ($request->type == 'excel') {
            return Excel::download(new DisbursementHistoryExport($data), 'Disbursementlist.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new DisbursementHistoryExport($data), 'Disbursementlist.csv');
        }
    }

}
