<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\CentralLogics\CustomerLogic;
use App\Exports\CustomerWalletTransactionExport;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;


class CustomerWalletController extends Controller
{
    public function add_fund_view()
    {
        if (BusinessSetting::where('key', 'wallet_status')->first()->value != 1) {
            Toastr::error(trans('messages.customer_wallet_disable_warning_admin'));
            return back();
        }
        return view('admin-views.customer.wallet.add_fund');
    }

    /**
     * Build the JSON error shape the add_fund blade actually consumes.
     *
     * The view iterates `data.errors[i].message`, so the payload must be a LIST
     * of objects. HTTP 200 is intentional: the handler inspects the body only,
     * and a non-200 would leave the operator with no feedback at all.
     */
    private function fund_error(string $message)
    {
        return response()->json(['errors' => [
            ['code' => 'add_fund', 'message' => $message],
        ]], 200);
    }

    /**
     * Admin manual wallet funding. Hardened in phase B.4.
     *
     * Order is deliberate and must not be rearranged:
     *   auth (middleware) -> module permission (middleware) -> password ->
     *   validation -> transaction -> status/idempotency -> wallet -> audit -> commit
     *
     * Password failures are audited because they are security events.
     * Ordinary validation failures are not.
     */
    public function add_fund(Request $request)
    {
        $admin = auth('admin')->user();

        // --- Step 3: password re-authentication -----------------------------
        // Throttled per admin id, never per IP: multiple administrators commonly
        // share one public IP and must not lock each other out.
        $throttle_key = 'admin-fund-password:' . ($admin->id ?? 'unknown');

        if (RateLimiter::tooManyAttempts($throttle_key, 5)) {
            $seconds = RateLimiter::availableIn($throttle_key);
            AuditLog::record(
                'admin_wallet_fund_locked', 'User', null, null, null, null,
                ['seconds_remaining' => $seconds, 'endpoint' => $request->path()]
            );
            return $this->fund_error(trans('messages.too_many_password_attempts') . ' ' . $seconds . 's');
        }

        if (!$request->filled('admin_password') || !Hash::check($request->admin_password, $admin->password)) {
            RateLimiter::hit($throttle_key, 300);
            AuditLog::record(
                'admin_wallet_fund_denied', 'User', null, null, null, null,
                [
                    'reason'              => 'invalid_password',
                    'target_customer_id'  => $request->customer_id,
                    'attempts_remaining'  => RateLimiter::remaining($throttle_key, 5),
                    'endpoint'            => $request->path(),
                ]
            );
            return $this->fund_error(trans('messages.incorrect_password'));
        }

        RateLimiter::clear($throttle_key);

        // --- Step 4: validation ---------------------------------------------
        // `required` was previously absent: a missing customer_id passed the
        // `exists` rule and reached the wallet engine as null.
        // `reference` is the mandatory funding reason. No second field exists.
        $validator = Validator::make($request->all(), [
            'customer_id'=>'required|exists:users,id',
            'amount'=>'required|numeric|min:.01',
            'reference'=>'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        // --- Steps 5-8: transaction, wallet mutation, audit, commit ----------
        try {
            $wallet_transaction = DB::transaction(function () use ($request, $admin) {
                // Serialises concurrent submissions on the same customer.
                $customer = User::where('id', $request->customer_id)->lockForUpdate()->first();

                if (!$customer) {
                    throw new \RuntimeException(trans('messages.customer_not_found'));
                }

                // Existing application status mechanism (users.status), the same
                // flag the customer auth flow enforces. No new state introduced.
                if (!$customer->status) {
                    throw new \RuntimeException(trans('messages.customer_account_is_inactive'));
                }

                // Idempotency: a double submit inside the lock window is rejected
                // once the first has committed, so a duplicate credit is impossible.
                $duplicate = WalletTransaction::where('user_id', $customer->id)
                    ->where('transaction_type', 'add_fund_by_admin')
                    ->where('credit', (float) $request->amount)
                    ->where('reference', $request->reference)
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->first();

                if ($duplicate) {
                    throw new \RuntimeException(trans('messages.duplicate_fund_request_ignored'));
                }

                $balance_before = $customer->wallet_balance;

                // CustomerLogic returns false instead of throwing, so a falsy
                // result must abort this transaction explicitly.
                $transaction = CustomerLogic::create_wallet_transaction(
                    $customer->id, (float) $request->amount, 'add_fund_by_admin', $request->reference
                );

                if (!$transaction) {
                    throw new \RuntimeException(trans('messages.failed_to_create_transaction'));
                }

                // Audited inside the transaction: unlike B.1 there is no external
                // side effect here, so money and its audit row commit atomically.
                AuditLog::record(
                    'admin_wallet_fund',
                    'User',
                    $customer->id,
                    ['wallet_balance' => $balance_before],
                    ['wallet_balance' => $customer->fresh()->wallet_balance],
                    null,
                    [
                        'amount'         => (float) $request->amount,
                        'reference'      => $request->reference,
                        'transaction_id' => is_object($transaction) ? ($transaction->transaction_id ?? null) : null,
                        'endpoint'       => $request->path(),
                    ]
                );

                return $transaction;
            });
        } catch (\RuntimeException $ex) {
            // Deliberate, user-facing refusals.
            return $this->fund_error($ex->getMessage());
        } catch (\Throwable $ex) {
            // Unexpected failure: detail to the log, generic message to the operator.
            Log::error('Admin wallet funding failed', [
                'admin_id'    => $admin->id ?? null,
                'customer_id' => $request->customer_id,
                'amount'      => $request->amount,
                'error'       => $ex->getMessage(),
            ]);
            return $this->fund_error(trans('messages.failed_to_create_transaction'));
        }

        if($wallet_transaction)
        {
            try{
                Helpers::add_fund_push_notification($request->customer_id);
                if(config('mail.status') && Helpers::get_mail_status('add_fund_mail_status_user') == '1' &&  Helpers::getNotificationStatusData('customer','customer_add_fund_to_wallet','mail_status') ) {
                    Mail::to($wallet_transaction->user->email)->send(new \App\Mail\AddFundToWallet($wallet_transaction));
                }
            }catch(\Exception $ex)
            {
                info($ex->getMessage());
            }

            return response()->json([], 200);
        }

        return $this->fund_error(trans('messages.failed_to_create_transaction'));
    }

    public function report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $filter = $request->query('filter', 'all_time');
        $key = [];
        if ($request->search) {
            $key = explode(' ', $request['search']);
        }
        $data = WalletTransaction::selectRaw('sum(credit+admin_bonus) as total_credit, sum(debit) as total_debit, SUM(IF(transaction_type = "add_fund_by_admin", credit, 0)) as add_fund_total,SUM(IF(transaction_type = "order_refund", credit, 0)) as order_refund_total,SUM(IF(transaction_type = "loyalty_point", credit, 0)) as loyalty_point_total,SUM(IF(transaction_type = "order_place", credit, 0)) as order_place_total')
            ->when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
        ->get();

        $transactions = WalletTransaction::with('user')->
            when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
        ->latest()
        ->paginate(config('default_pagination'));

        return view('admin-views.customer.wallet.report', compact('data','transactions','filter'));
    }

    public function export(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $filter = $request->query('filter', 'all_time');
        $key = [];
        if ($request->search) {
            $key = explode(' ', $request['search']);
        }

        $data = WalletTransaction::selectRaw('sum(credit) as total_credit, sum(debit) as total_debit')
            ->when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
       ->get();

        $transactions = WalletTransaction::
            when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($request->transaction_type) && ($request->transaction_type != 'all'), function($query)use($request){
                $query->where('transaction_type',$request->transaction_type);
            })
            ->when(isset($request->customer_id) && is_numeric($request->customer_id), function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
        ->when(count($key) > 0, function($query) use($key){
            $query->wherehas('user',    function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where(function($query) use($value){
                        $query->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                    });
                };
            });
       })
        ->latest()
        ->get();

        $data = [
            'transactions'=>$transactions,
            'data'=>$data,
            'from'=>$request->from??null,
            'to'=>$request->to??null,
            'transaction_type'=>$request->transaction_type??null,
            'customer'=>$request->customer_id?Helpers::get_customer_name($request->customer_id):$request['search']?? null,

        ];

        if ($request->type == 'excel') {
            return Excel::download(new CustomerWalletTransactionExport($data), 'CustomerWalletTransactions.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new CustomerWalletTransactionExport($data), 'CustomerWalletTransactions.csv');
        }
    }

    public function set_date(Request $request)
    {
        session()->put('from_date', date('Y-m-d', strtotime($request['from'])));
        session()->put('to_date', date('Y-m-d', strtotime($request['to'])));
        return back();
    }

}
