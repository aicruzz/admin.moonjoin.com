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
        // -------------------------------------------------------------------------
        // Step 1: Validate inputs
        // -------------------------------------------------------------------------
        \Illuminate\Support\Facades\Log::info('9PSB w_request started', [
            'vendor_id' => Helpers::get_vendor_id(),
            'amount' => $request['amount'],
            'account_number' => $request['account_number'],
            'account_name' => $request['account_name'],
            'withdraw_method' => $request['withdraw_method'],
        ]);

        $validator = Validator::make($request->all(), [
            'withdraw_method' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'account_number' => 'required|string|size:10',
            'bank_code' => 'nullable|string',
            'account_name' => 'required|string|max:255',
            'narration' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('9PSB w_request validation failed', [
                'vendor_id' => Helpers::get_vendor_id(),
                'errors' => $validator->errors()->toArray(),
            ]);
            Toastr::error($validator->errors()->first());
            return redirect()->back();
        }

        \Illuminate\Support\Facades\Log::info('9PSB w_request validation passed', [
            'vendor_id' => Helpers::get_vendor_id(),
        ]);

        // -------------------------------------------------------------------------
        // Step 2: Build withdrawal method fields
        // -------------------------------------------------------------------------
        $method = WithdrawalMethod::find($request['withdraw_method']);

        if (!$method) {
            \Illuminate\Support\Facades\Log::error('9PSB w_request withdrawal method not found', [
                'vendor_id' => Helpers::get_vendor_id(),
                'withdraw_method' => $request['withdraw_method'],
            ]);
            Toastr::error('Invalid withdrawal method selected.');
            return redirect()->back();
        }

        \Illuminate\Support\Facades\Log::info('9PSB w_request method loaded', [
            'vendor_id' => Helpers::get_vendor_id(),
            'method_id' => $method->id,
            'method_name' => $method->method_name,
        ]);

        $fields = array_column($method->method_fields, 'input_name');
        $values = $request->all();

        $method_data = [];
        foreach ($fields as $field) {
            if (key_exists($field, $values)) {
                $method_data[$field] = $values[$field];
            }
        }

        \Illuminate\Support\Facades\Log::info('9PSB w_request method_data built', [
            'vendor_id' => Helpers::get_vendor_id(),
            'method_data' => $method_data,
        ]);

        // -------------------------------------------------------------------------
        // Step 2b: Resolve bank code from method name (hardcoded map)
        // -------------------------------------------------------------------------
        $methodNameNormalized = strtolower(trim($method->method_name ?? ''));

        $bankCodeMap = [
            'opay' => '100004',
            'palmpay' => '100033',
            'airpero' => '090133',
            'bank transfer' => $request['bank_code'] ?? '100004',
        ];

        $resolvedBankCode = $request['bank_code'] ?? '100004'; // fallback
        foreach ($bankCodeMap as $keyword => $code) {
            if (str_contains($methodNameNormalized, $keyword)) {
                $resolvedBankCode = $code;
                break;
            }
        }

        \Illuminate\Support\Facades\Log::info('9PSB w_request bank code resolved', [
            'vendor_id' => Helpers::get_vendor_id(),
            'method_name' => $method->method_name,
            'method_normalized' => $methodNameNormalized,
            'resolved_bank_code' => $resolvedBankCode,
            'form_bank_code' => $request['bank_code'],
        ]);

        // -------------------------------------------------------------------------
        // Step 3: Balance check
        // -------------------------------------------------------------------------
        $w = StoreWallet::where('vendor_id', Helpers::get_vendor_id())->first();

        if (!$w) {
            \Illuminate\Support\Facades\Log::error('9PSB w_request wallet not found', [
                'vendor_id' => Helpers::get_vendor_id(),
            ]);
            Toastr::error('Wallet not found.');
            return redirect()->back();
        }

        \Illuminate\Support\Facades\Log::info('9PSB w_request balance check', [
            'vendor_id' => Helpers::get_vendor_id(),
            'wallet_balance' => $w->balance,
            'requested_amount' => $request['amount'],
        ]);

        if (!($w->balance >= $request['amount'])) {
            \Illuminate\Support\Facades\Log::warning('9PSB w_request insufficient balance', [
                'vendor_id' => Helpers::get_vendor_id(),
                'wallet_balance' => $w->balance,
                'requested_amount' => $request['amount'],
            ]);
            Toastr::error('Insufficient balance.');
            return redirect()->back();
        }

        // -------------------------------------------------------------------------
        // Step 4: 9PSB credentials
        // -------------------------------------------------------------------------
        $baseUrl = rtrim(config('services.ninepsb.base_url', 'https://sandbox.v1.airpero.com/'), '/');
        $publicKey = config('services.ninepsb.public_key');
        $privateKey = config('services.ninepsb.private_key');

        \Illuminate\Support\Facades\Log::info('9PSB w_request credentials loaded', [
            'vendor_id' => Helpers::get_vendor_id(),
            'base_url' => $baseUrl,
            'has_public_key' => !empty($publicKey),
            'has_private_key' => !empty($privateKey),
        ]);

        // -------------------------------------------------------------------------
        // Step 5: Name Enquiry — verify account BEFORE sending payout
        // -------------------------------------------------------------------------
        \Illuminate\Support\Facades\Log::info('9PSB w_request nameEnquiry initiating', [
            'vendor_id' => Helpers::get_vendor_id(),
            'accountNumber' => $request['account_number'],
            'bankCode' => $resolvedBankCode,
        ]);

        try {
            $enquiryResponse = \Illuminate\Support\Facades\Http::baseUrl($baseUrl)
                ->withHeaders([
                    'x-public-key' => $publicKey,
                    'x-private-key' => $privateKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->retry(2, sleepMilliseconds: 500, throw: false)
                ->post('/api/v1/banks/enquiry', [
                    'accountNumber' => $request['account_number'],
                    'bankCode' => $resolvedBankCode,
                ]);

            \Illuminate\Support\Facades\Log::info('9PSB w_request nameEnquiry response', [
                'vendor_id' => Helpers::get_vendor_id(),
                'http_status' => $enquiryResponse->status(),
                'body' => $enquiryResponse->json() ?? $enquiryResponse->body(),
                'accountNumber' => $request['account_number'],
                'bankCode' => $resolvedBankCode,
            ]);

            if (!$enquiryResponse->successful()) {
                \Illuminate\Support\Facades\Log::warning('9PSB w_request nameEnquiry failed', [
                    'vendor_id' => Helpers::get_vendor_id(),
                    'http_status' => $enquiryResponse->status(),
                    'message' => $enquiryResponse->json('message'),
                ]);
                Toastr::error($enquiryResponse->json('message') ?? 'Bank account verification failed.');
                return redirect()->back();
            }

            $resolvedName = $enquiryResponse->json('data.accountName');

            \Illuminate\Support\Facades\Log::info('9PSB w_request nameEnquiry resolved name', [
                'vendor_id' => Helpers::get_vendor_id(),
                'resolvedName' => $resolvedName,
            ]);

            if (!$resolvedName) {
                \Illuminate\Support\Facades\Log::warning('9PSB w_request nameEnquiry no account name returned', [
                    'vendor_id' => Helpers::get_vendor_id(),
                    'full_body' => $enquiryResponse->json(),
                ]);
                Toastr::error('Account name could not be resolved. Please check your account number and bank.');
                return redirect()->back();
            }

            // Fuzzy match — handles minor spacing/case differences
            similar_text(
                strtolower(trim($resolvedName)),
                strtolower(trim($request['account_name'])),
                $matchPercent
            );

            \Illuminate\Support\Facades\Log::info('9PSB w_request name match result', [
                'vendor_id' => Helpers::get_vendor_id(),
                'resolved' => $resolvedName,
                'submitted' => $request['account_name'],
                'match_percent' => $matchPercent,
                'passed' => $matchPercent >= 70,
            ]);

            if ($matchPercent < 70) {
                \Illuminate\Support\Facades\Log::warning('9PSB w_request name match failed', [
                    'vendor_id' => Helpers::get_vendor_id(),
                    'resolved' => $resolvedName,
                    'submitted' => $request['account_name'],
                    'match_percent' => $matchPercent,
                ]);
                Toastr::error('Account name does not match. Expected: ' . $resolvedName);
                return redirect()->back();
            }

            $verifiedAccountName = $resolvedName;

            \Illuminate\Support\Facades\Log::info('9PSB w_request account verified, proceeding to payout', [
                'vendor_id' => Helpers::get_vendor_id(),
                'verifiedAccountName' => $verifiedAccountName,
                'match_percent' => $matchPercent,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('9PSB w_request nameEnquiry exception', [
                'vendor_id' => Helpers::get_vendor_id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Toastr::error('Account verification failed: ' . $e->getMessage());
            return redirect()->back();
        }

        // -------------------------------------------------------------------------
        // Step 6: Fire payout to bank — only reached after enquiry passes
        // -------------------------------------------------------------------------
        \Illuminate\Support\Facades\Log::info('9PSB w_request payout initiating', [
            'vendor_id' => Helpers::get_vendor_id(),
            'amount' => $request['amount'],
            'accountNumber' => $request['account_number'],
            'bankCode' => $resolvedBankCode,
            'accountName' => $verifiedAccountName,
            'narration' => $request['narration'] ?? 'Vendor withdrawal',
        ]);

        try {
            $payoutResponse = \Illuminate\Support\Facades\Http::baseUrl($baseUrl)
                ->withHeaders([
                    'x-public-key' => $publicKey,
                    'x-private-key' => $privateKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->retry(2, sleepMilliseconds: 500, throw: false)
                ->post('/api/v1/merchants/payout', [
                    'amount' => $request['amount'],
                    'accountNumber' => $request['account_number'],
                    'bankCode' => $resolvedBankCode,
                    'accountName' => $verifiedAccountName,
                    'narration' => $request['narration'] ?? 'Vendor withdrawal',
                ]);

            \Illuminate\Support\Facades\Log::info('9PSB w_request payout response', [
                'vendor_id' => Helpers::get_vendor_id(),
                'http_status' => $payoutResponse->status(),
                'body' => $payoutResponse->json() ?? $payoutResponse->body(),
                'amount' => $request['amount'],
                'bankCode' => $resolvedBankCode,
            ]);

            if (!$payoutResponse->successful()) {
                \Illuminate\Support\Facades\Log::warning('9PSB w_request payout failed', [
                    'vendor_id' => Helpers::get_vendor_id(),
                    'http_status' => $payoutResponse->status(),
                    'message' => $payoutResponse->json('message'),
                    'amount' => $request['amount'],
                ]);
                Toastr::error($payoutResponse->json('message') ?? 'Payout request failed.');
                return redirect()->back();
            }

            \Illuminate\Support\Facades\Log::info('9PSB w_request payout successful', [
                'vendor_id' => Helpers::get_vendor_id(),
                'amount' => $request['amount'],
                'response' => $payoutResponse->json(),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('9PSB w_request payout exception', [
                'vendor_id' => Helpers::get_vendor_id(),
                'amount' => $request['amount'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Toastr::error('Bank transfer failed: ' . $e->getMessage());
            return redirect()->back();
        }

        // -------------------------------------------------------------------------
        // Step 7: Payout done — write to DB + deduct balance inside lock
        // -------------------------------------------------------------------------
        $data = [
            'vendor_id' => Helpers::get_vendor_id(),
            'amount' => $request['amount'],
            'transaction_note' => null,
            'withdrawal_method_id' => $request['withdraw_method'],
            'withdrawal_method_fields' => json_encode($method_data),
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        \Illuminate\Support\Facades\Log::info('9PSB w_request writing to DB', [
            'vendor_id' => Helpers::get_vendor_id(),
            'data' => $data,
        ]);

        try {
            DB::transaction(function () use ($data, $w, $request) {
                \Illuminate\Support\Facades\Log::info('9PSB w_request DB transaction started', [
                    'vendor_id' => $w->vendor_id,
                    'amount' => $request['amount'],
                ]);

                $wallet = StoreWallet::where('vendor_id', $w->vendor_id)
                    ->lockForUpdate()
                    ->first();

                \Illuminate\Support\Facades\Log::info('9PSB w_request wallet locked', [
                    'vendor_id' => $w->vendor_id,
                    'wallet_balance' => $wallet->balance,
                    'amount' => $request['amount'],
                ]);

                if ($wallet->balance < $request['amount']) {
                    \Illuminate\Support\Facades\Log::warning('9PSB w_request balance insufficient inside lock', [
                        'vendor_id' => $w->vendor_id,
                        'wallet_balance' => $wallet->balance,
                        'amount' => $request['amount'],
                    ]);
                    throw new \Exception('insufficient_balance');
                }

                DB::table('withdraw_requests')->insert($data);

                \Illuminate\Support\Facades\Log::info('9PSB w_request withdraw_request inserted', [
                    'vendor_id' => $w->vendor_id,
                    'amount' => $request['amount'],
                ]);

                $wallet->decrement('balance', $request['amount']);

                \Illuminate\Support\Facades\Log::info('9PSB w_request wallet balance decremented', [
                    'vendor_id' => $w->vendor_id,
                    'amount' => $request['amount'],
                    'balance_after' => $wallet->fresh()->balance,
                ]);
            });

        } catch (\Exception $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                Toastr::error('Insufficient balance.');
                return redirect()->back();
            }

            \Illuminate\Support\Facades\Log::critical('9PSB payout sent but DB write failed', [
                'vendor_id' => Helpers::get_vendor_id(),
                'amount' => $request['amount'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payout' => $payoutResponse->json(),
            ]);

            Toastr::error('Payment was sent but record could not be saved. Please contact support.');
            return redirect()->back();
        }

        // -------------------------------------------------------------------------
        // Step 8: Send notification email
        // -------------------------------------------------------------------------
        \Illuminate\Support\Facades\Log::info('9PSB w_request sending notification email', [
            'vendor_id' => Helpers::get_vendor_id(),
        ]);

        try {
            $admin = Admin::where('role_id', 1)->first();
            $wallet_transaction = WithdrawRequest::where('vendor_id', Helpers::get_vendor_id())->latest()->first();

            \Illuminate\Support\Facades\Log::info('9PSB w_request email data loaded', [
                'vendor_id' => Helpers::get_vendor_id(),
                'admin_email' => $admin['email'] ?? null,
                'wallet_transaction_id' => $wallet_transaction->id ?? null,
                'module_type' => Helpers::get_store_data()?->module?->module_type,
            ]);

            if (
                Helpers::get_store_data()?->module?->module_type !== 'rental' &&
                config('mail.status') &&
                Helpers::get_mail_status('withdraw_request_mail_status_admin') == '1' &&
                Helpers::getNotificationStatusData('admin', 'withdraw_request', 'mail_status')
            ) {
                Mail::to($admin['email'])->send(new WithdrawRequestMail('admin_mail', $wallet_transaction));
                \Illuminate\Support\Facades\Log::info('9PSB w_request admin withdraw mail sent', [
                    'vendor_id' => Helpers::get_vendor_id(),
                    'admin_email' => $admin['email'],
                ]);

            } elseif (
                Helpers::get_store_data()?->module?->module_type == 'rental' &&
                addon_published_status('Rental') &&
                config('mail.status') &&
                Helpers::get_mail_status('rental_withdraw_request_mail_status_admin') == '1' &&
                Helpers::getRentalNotificationStatusData('admin', 'provider_withdraw_request', 'mail_status')
            ) {
                Mail::to($admin['email'])->send(new ProviderWithdrawRequestMail('pending', $wallet_transaction));
                \Illuminate\Support\Facades\Log::info('9PSB w_request rental provider withdraw mail sent', [
                    'vendor_id' => Helpers::get_vendor_id(),
                    'admin_email' => $admin['email'],
                ]);
            } else {
                \Illuminate\Support\Facades\Log::info('9PSB w_request email skipped (conditions not met)', [
                    'vendor_id' => Helpers::get_vendor_id(),
                    'mail_status' => config('mail.status'),
                ]);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('9PSB w_request mail failed', [
                'vendor_id' => Helpers::get_vendor_id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        \Illuminate\Support\Facades\Log::info('9PSB w_request completed successfully', [
            'vendor_id' => Helpers::get_vendor_id(),
            'amount' => $request['amount'],
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
