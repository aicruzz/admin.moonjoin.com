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
        \Log::info('VENDOR WITHDRAW STEP 1 - REQUEST HIT', $request->all());

        $method = WithdrawalMethod::find($request['withdraw_method']);
        $fields = array_column($method->method_fields, 'input_name');
        $values = $request->all();

        $method_data = [];
        foreach ($fields as $field) {
            if (key_exists($field, $values)) {
                $method_data[$field] = $values[$field];
            }
        }

        \Log::info('VENDOR WITHDRAW STEP 3 - METHOD DATA CAPTURED', [
            'method_data' => $method_data,
            'account_number' => $method_data['account_number'] ?? 'NOT FOUND',
        ]);

        $w = StoreWallet::where('vendor_id', Helpers::get_vendor_id())->first();

        \Log::info('VENDOR WITHDRAW STEP 4 - WALLET CHECK', [
            'wallet_balance' => $w?->balance ?? 'NULL',
            'requested_amount' => $request['amount'],
            'has_enough' => ((string) $w?->balance >= (string) $request['amount']) ? 'YES' : 'NO',
        ]);

        if ((string) $w->balance >= (string) $request['amount'] && (string) $request['amount'] > .01) {
            try {
                // ---------------------------------------------------------------
                // IDEMPOTENCY LOCK
                // ---------------------------------------------------------------
                $withdraw = DB::transaction(function () use ($w, $request, $method_data) {

                    // Lock this vendor's wallet row for the duration of the transaction
                    $wallet = StoreWallet::where('vendor_id', $w->vendor_id)
                        ->lockForUpdate()
                        ->first();

                    // Check for a pending withdraw created in last 2 minutes
                    $existing = WithdrawRequest::where('vendor_id', $wallet->vendor_id)
                        ->where('amount', $request['amount'])
                        ->where('approved', 0)
                        ->where('created_at', '>=', now()->subMinutes(2))
                        ->first();

                    if ($existing) {
                        \Log::warning('VENDOR IDEMPOTENCY - Reusing existing withdraw request', [
                            'withdraw_id' => $existing->id,
                        ]);
                        return $existing;
                    }

                    // Fresh insert — safe because wallet row is locked
                    $data = [
                        'vendor_id' => $wallet->vendor_id,
                        'amount' => $request['amount'],
                        'transaction_note' => null,
                        'withdrawal_method_id' => $request['withdraw_method'],
                        'withdrawal_method_fields' => json_encode($method_data),
                        'approved' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    DB::table('withdraw_requests')->insert($data);
                    
                    $wallet->increment('pending_withdraw', $request['amount']);

                    return WithdrawRequest::where('vendor_id', $wallet->vendor_id)
                        ->latest()
                        ->first();
                });

                \Log::info('VENDOR WITHDRAW STEP 5 - WITHDRAW SAVED', [
                    'withdraw_id' => $withdraw->id,
                    'amount' => $withdraw->amount,
                    'approved' => $withdraw->approved,
                ]);

                // 9PSB AUTO-PAYOUT
                $account_number = $method_data['account_number'] ?? null;
                $method_name = strtolower(trim($method->method_name ?? ''));
                $bank_code_from_form = $method_data['bank_code'] ?? null;

                $bankCodeMap = [
                    'opay' => '100004',
                    'moniepoint' => '090405',
                    'palmpay' => '100033',
                    'smartcash psb' => '120001',
                    'access bank plc' => '000014',
                    'united bank for africa' => '000004',
                    'kuda microfinance bank' => '090267',
                    'airpero' => '090133',
                    'bank transfer' => $bank_code_from_form,
                ];

                $bank_code = $bankCodeMap[$method_name] ?? $bank_code_from_form;

                \Log::info('VENDOR WITHDRAW STEP 6 - BANK CODE LOOKUP', [
                    'method_name' => $method_name,
                    'bank_code' => $bank_code ?? 'NOT MAPPED YET',
                    'account_number' => $account_number ?? 'NULL',
                ]);

                if ($account_number) {
                    try {
                        $ninePsb = new \App\Http\Controllers\Api\V1\NinePsbPaymentController();

                        // STEP 7 — Get bank list
                        $banksRaw = json_decode($ninePsb->getBanks()->getContent(), true);

                        \Log::info('VENDOR WITHDRAW STEP 7 - GET BANKS RESPONSE', [
                            'success' => $banksRaw['success'] ?? 'NULL',
                            'data_keys' => array_keys($banksRaw['data'] ?? []),
                        ]);

                        $bankList = $banksRaw['data']['data']['bankList']
                            ?? $banksRaw['data']['bankList']
                            ?? $banksRaw['data']['data']['data']['bankList']
                            ?? [];

                        \Log::info('VENDOR WITHDRAW STEP 7B - BANK LIST COUNT', [
                            'count' => count($bankList),
                        ]);

                        $matchedBank = null;
                        if ($bank_code) {
                            $matchedBank = collect($bankList)->firstWhere('bankCode', $bank_code);
                        }

                        // Fuzzy match if no direct bank_code match
                        if (!$matchedBank && $method_name) {
                            $matchedBank = collect($bankList)->first(function ($b) use ($method_name) {
                                $apiName = strtolower(trim($b['bankName'] ?? ''));
                                if (str_contains($apiName, $method_name) || str_contains($method_name, $apiName)) {
                                    return true;
                                }
                                if ($method_name === 'united bank for africa' && in_array($apiName, ['uba', 'united bank for africa'])) {
                                    return true;
                                }
                                if (str_contains($method_name, 'smartcash') && str_contains($apiName, 'smartcash')) {
                                    return true;
                                }
                                return false;
                            });

                            if ($matchedBank) {
                                $bank_code = $matchedBank['bankCode'];
                            }
                        }

                        \Log::info('VENDOR WITHDRAW STEP 7C - BANK MATCH', [
                            'bank_code' => $bank_code,
                            'matched_bank' => $matchedBank ?? 'NOT FOUND',
                        ]);

                        if (!$matchedBank || !$bank_code) {
                            \Log::error('VENDOR WITHDRAW STEP 7 FAILED - Bank code not found', [
                                'bank_code' => $bank_code,
                                'method_name' => $method_name,
                            ]);
                            goto send_notification;
                        }

                        $bank_name = $matchedBank['bankName'];

                        // STEP 8 — Name enquiry
                        $enquiryResult = $ninePsb->nameEnquiryDirect($account_number, $bank_code);

                        \Log::info('VENDOR WITHDRAW STEP 8 - NAME ENQUIRY RESPONSE', $enquiryResult ?? []);

                        if (!$enquiryResult['success']) {
                            \Log::error('VENDOR WITHDRAW STEP 8 FAILED - Account name not found', [
                                'raw' => $enquiryResult,
                            ]);
                            goto send_notification;
                        }

                        $account_name = $enquiryResult['accountName'];

                        \Log::info('VENDOR WITHDRAW STEP 8 SUCCESS - Account name resolved', [
                            'account_name' => $account_name,
                            'bank_name' => $bank_name,
                        ]);

                        // STEP 9 — Fire payout
                        // Re-fetch fresh from DB to guard against approved by another request
                        $freshWithdraw = WithdrawRequest::find($withdraw->id);

                        if ($freshWithdraw->approved == 1) {
                            \Log::warning('VENDOR WITHDRAW STEP 9 SKIPPED - Already approved, duplicate payout prevented', [
                                'withdraw_id' => $withdraw->id,
                            ]);
                            goto send_notification;
                        }

                        // Lock the withdraw row before paying to prevent race on payout
                        DB::transaction(function () use ($freshWithdraw, $ninePsb, $account_number, $bank_code, $account_name, $bank_name, $w, &$withdraw) {
                            $locked = WithdrawRequest::where('id', $freshWithdraw->id)
                                ->where('approved', 0)
                                ->lockForUpdate()
                                ->first();

                            // Another process already approved it while we were waiting
                            if (!$locked) {
                                \Log::warning('VENDOR WITHDRAW STEP 9 SKIPPED INSIDE LOCK - Already approved', [
                                    'withdraw_id' => $freshWithdraw->id,
                                ]);
                                return;
                            }

                            $payoutResult = $ninePsb->payoutStoreToBankDirect(
                                (float) $locked->amount,
                                $account_number,
                                $bank_code,
                                $account_name,
                                'Vendor Fast Withdraw ID: ' . $locked->id
                            );

                            \Log::info('VENDOR WITHDRAW STEP 9 - PAYOUT RESPONSE', $payoutResult ?? []);

                            if (!$payoutResult['success']) {
                                \Log::error('VENDOR WITHDRAW STEP 9 FAILED - Payout failed', [
                                    'message' => $payoutResult['message'] ?? 'Unknown',
                                ]);
                                return; // exits transaction, goto send_notification below
                            }

                            // STEP 10 — Auto approve inside the lock
                            $locked->approved = 1;
                            $locked->transaction_note = 'Auto-paid via 9PSB to '
                                . $account_name
                                . ' (' . $bank_name . ') on '
                                . now()->toDateTimeString();
                            $locked->save();

                            $w->increment('total_withdrawn', $locked->amount);
                            $w->decrement('pending_withdraw', $locked->amount);

                            \Log::info('VENDOR WITHDRAW STEP 10 SUCCESS - AUTO APPROVED', [
                                'withdraw_id' => $locked->id,
                                'account_name' => $account_name,
                                'bank_name' => $bank_name,
                                'amount' => $locked->amount,
                            ]);

                            $withdraw = $locked;
                        });

                    } catch (\Exception $e) {
                        \Log::error('VENDOR 9PSB EXCEPTION', [
                            'withdraw_id' => $withdraw->id,
                            'error' => $e->getMessage(),
                            'line' => $e->getLine(),
                            'file' => $e->getFile(),
                        ]);
                    }
                } else {
                    \Log::warning('VENDOR WITHDRAW STEP 6 SKIPPED - No account number or bank code', [
                        'account_number' => $account_number ?? 'NULL',
                        'bank_code' => $bank_code ?? 'NULL',
                        'method_name' => $method_name,
                    ]);
                }

                send_notification:

                try {
                    $admin = Admin::where('role_id', 1)->first();
                    $wallet_transaction = $withdraw; // WithdrawRequestMail needs the model
                    if (Helpers::get_store_data()?->module?->module_type !== 'rental' && config('mail.status') && Helpers::get_mail_status('withdraw_request_mail_status_admin') == '1' && Helpers::getNotificationStatusData('admin', 'withdraw_request', 'mail_status')) {
                        Mail::to($admin['email'])->send(new WithdrawRequestMail('pending', $wallet_transaction));
                    } elseif (Helpers::get_store_data()?->module?->module_type == 'rental' && addon_published_status('Rental') && config('mail.status') && Helpers::get_mail_status('rental_withdraw_request_mail_status_admin') == '1' && Helpers::getRentalNotificationStatusData('admin', 'provider_withdraw_request', 'mail_status')) {
                        Mail::to($admin['email'])->send(new ProviderWithdrawRequestMail('pending', $wallet_transaction));
                    }
                } catch (\Exception $e) {
                    info($e->getMessage());
                }

                Toastr::success('Withdraw request has been sent.');
                return redirect()->back();

            } catch (\Exception $e) {
                \Log::error('VENDOR WITHDRAW REQUEST EXCEPTION', [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
                info($e->getMessage());
                Toastr::error('Withdraw request failed due to an error.');
                return redirect()->back();
            }
        }

        Toastr::error('invalid request.!');
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
