<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorEmployee;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeWalletTransaction;
use App\Http\Controllers\Api\V1\NinePsbPaymentController;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;

class EmployeeWalletController extends Controller
{
    public function index(Request $request)
    {
        $store_id = \App\CentralLogics\Helpers::get_store_id();

        if (auth('vendor_employee')->check()) {

            $employee = auth('vendor_employee')->user();

            $ve_disbursement_type = \App\Models\BusinessSetting::where('key', 've_disbursement_type')
                ->first()?->value ?? 'manual';

            $transactions = EmployeeWalletTransaction::where('employee_id', $employee->id)
                ->where('store_id', $store_id)
                ->latest()
                ->paginate(10);

            $total_debited = EmployeeWalletTransaction::where('employee_id', $employee->id)
                ->where('store_id', $store_id)
                ->where('type', 'debit')
                ->sum('amount');

            $total_credited = EmployeeWalletTransaction::where('employee_id', $employee->id)
                ->where('store_id', $store_id)
                ->where('type', 'credit')
                ->sum('amount');

            $pending_withdraw = EmployeeWalletTransaction::where('employee_id', $employee->id)
                ->where('store_id', $store_id)
                ->where('type', 'withdraw')
                ->where('status', 'pending')
                ->sum('amount');

            $total_withdrawn = EmployeeWalletTransaction::where('employee_id', $employee->id)
                ->where('store_id', $store_id)
                ->where('type', 'withdraw')
                ->where('status', 'approved')
                ->sum('amount');

            $withdrawable_balance = max(
                0,
                ($employee->wallet_balance ?? 0) - $pending_withdraw
            );

            $withdrawal_methods = WithdrawalMethod::where('is_active', 1)->get();

            return view('vendor-views.employee-wallet.my-wallet', compact(
                'employee',
                'transactions',
                'total_debited',
                'total_credited',
                'pending_withdraw',
                'total_withdrawn',
                'withdrawable_balance',
                'withdrawal_methods',
                've_disbursement_type'
            ));
        }

        // ── Store Owner View (Debit Page) ────────────────────

        $employees = VendorEmployee::with('role')
            ->where('store_id', $store_id)
            ->get();

        $debit_records = EmployeeWalletTransaction::with('employee.role')
            ->where('store_id', $store_id)
            ->where('type', 'debit')
            ->when(
                $request->search,
                fn($q) =>
                $q->whereHas(
                    'employee',
                    fn($q2) =>
                    $q2->where('f_name', 'like', "%{$request->search}%")
                        ->orWhere('l_name', 'like', "%{$request->search}%")
                )
            )
            ->latest()
            ->paginate(10);

        return view('vendor-views.employee-wallet.index', compact(
            'employees',
            'debit_records'
        ));
    }
    public function debit(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:vendor_employees,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $store_id = \App\CentralLogics\Helpers::get_store_id();

        $employee = VendorEmployee::where('id', $request->employee_id)
            ->where('store_id', $store_id)
            ->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => __('Employee not found or does not belong to your store.')
            ], 404);
        }

        // Insufficient balance
        if ($employee->wallet_balance < $request->amount) {

            return response()->json([
                'status' => 'error',
                'message' => __('Insufficient balance. Employee only has ') .
                    \App\CentralLogics\Helpers::format_currency($employee->wallet_balance)
            ], 422);
        }

        // Debit wallet
        $employee->decrement('wallet_balance', $request->amount);

        // Save transaction
        EmployeeWalletTransaction::create([
            'employee_id' => $request->employee_id,
            'store_id' => $store_id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'note' => $request->note,
            'type' => 'debit',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('Wallet debited successfully.')
        ], 200);
    }

    public function credit_page(Request $request)
    {
        $store_id = \App\CentralLogics\Helpers::get_store_id();

        // Employees
        $employees = VendorEmployee::with('role')
            ->where('store_id', $store_id)
            ->get();

        // Credit records
        $credit_records = EmployeeWalletTransaction::with('employee.role')
            ->where('store_id', $store_id)
            ->where('type', 'credit')
            ->when(
                $request->search,
                fn($q) =>
                $q->whereHas(
                    'employee',
                    fn($q2) =>
                    $q2->where('f_name', 'like', "%{$request->search}%")
                        ->orWhere('l_name', 'like', "%{$request->search}%")
                )
            )
            ->latest()
            ->paginate(10);

        return view('vendor-views.employee-wallet.credit', compact(
            'employees',
            'credit_records'
        ));
    }

    public function credit(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:vendor_employees,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $store_id = \App\CentralLogics\Helpers::get_store_id();

        $employee = VendorEmployee::where('id', $request->employee_id)
            ->where('store_id', $store_id)
            ->first();

        if (!$employee) {

            return response()->json([
                'status' => 'error',
                'message' => __('Employee not found or does not belong to your store.')
            ], 404);
        }

        // Credit wallet
        $employee->increment('wallet_balance', $request->amount);

        // Save transaction
        EmployeeWalletTransaction::create([
            'employee_id' => $request->employee_id,
            'store_id' => $store_id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'note' => $request->note,
            'type' => 'credit',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('Wallet credited successfully.')
        ], 200);
    }

public function withdraw_request(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'bank_code' => 'required|string',
            'account_number' => 'required|string|size:10',
            'account_name' => 'required|string',
            'bank_name' => 'required|string',
        ]);

        \Log::info('EMPLOYEE WITHDRAW HIT', $request->all());

        $store_id = \App\CentralLogics\Helpers::get_store_id();
        $employee_user = auth('vendor_employee')->user();

        try {
            // ---------------------------------------------------------------
            // 1. DATABASE TRANSACTION & IDEMPOTENCY LOCK
            // ---------------------------------------------------------------
            $transaction = DB::transaction(function () use ($request, $employee_user, $store_id) {
                
                // Lock the employee's fresh record to prevent parallel request balance race conditions
                $employee = DB::table('vendor_employees') 
                    ->where('id', $employee_user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$employee) {
                    throw new \Exception('Employee record not found.');
                }

                // Idempotency check: Look for a duplicate transaction within the last 2 minutes
                $existing = EmployeeWalletTransaction::where('employee_id', $employee->id)
                    ->where('store_id', $store_id)
                    ->where('amount', $request->amount)
                    ->where('status', 'pending')
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->first();

                if ($existing) {
                    \Log::warning('EMPLOYEE IDEMPOTENCY LOCK TRIGGERED - Reusing request', ['id' => $existing->id]);
                    return $existing;
                }

                // Balance Calculation - CRITICAL FIX: Use the freshly locked $employee object, not $employee_user
                $pending_withdraw = EmployeeWalletTransaction::where('employee_id', $employee->id)
                    ->where('store_id', $store_id)
                    ->where('type', 'withdraw')
                    ->where('status', 'pending')
                    ->sum('amount');

                $current_balance = $employee->wallet_balance ?? 0;
                $withdrawable_balance = max(0, $current_balance - $pending_withdraw);

                if ($request->amount > $withdrawable_balance) {
                    throw new \Exception('Insufficient withdrawable balance.');
                }

                // Insert the pending transaction log
                return EmployeeWalletTransaction::create([
                    'employee_id' => $employee->id,
                    'store_id' => $store_id,
                    'amount' => $request->amount,
                    'type' => 'withdraw',
                    'status' => 'pending',
                    'reason' => 'Automated Withdrawal Request',
                    'withdrawal_method_fields' => json_encode([
                        'account_name' => $request->account_name,
                        'account_number' => $request->account_number,
                        'bank_code' => $request->bank_code,
                        'bank_name' => $request->bank_name,
                    ]),
                ]);
            });

        } catch (\Exception $e) {
            \Log::error('EMPLOYEE WITHDRAW PRE-CHECK FAILED', ['error' => $e->getMessage()]);
            return back()->with('error', __($e->getMessage()));
        }

        // If it was an idempotency hit that returned an already approved/processed item, bail early
        if ($transaction->status !== 'pending') {
            return back()->with('success', __('Withdrawal processing or already handled.'));
        }

        // ---------------------------------------------------------------
        // 2. 9PSB AUTO-PAYOUT PROCESSING
        // ---------------------------------------------------------------
        try {
            $ninePsb = new \App\Http\Controllers\Api\V1\NinePsbPaymentController();

            // STEP A: Name Enquiry validation for added safety before moving funds
            $enquiryResult = $ninePsb->nameEnquiryDirect($request->account_number, $request->bank_code);
            
            \Log::info('EMPLOYEE WITHDRAW - NAME ENQUIRY RESPONSE', $enquiryResult ?? []);

            if (!$enquiryResult['success']) {
                \Log::error('EMPLOYEE WITHDRAW FAILED - Account verification failed');
                return back()->with('error', __('Account name verification failed with the provider.'));
            }

            $verified_account_name = $enquiryResult['accountName'];

            // STEP B: Execute locked payout execution
            $payoutSuccess = false;
            $payoutMessage = '';

            DB::transaction(function () use ($transaction, $ninePsb, $request, $verified_account_name, &$payoutSuccess, &$payoutMessage) {
                // Secure a lock on the transaction row to protect against multi-thread api execution
                $lockedTx = EmployeeWalletTransaction::where('id', $transaction->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (!$lockedTx) {
                    $payoutMessage = 'Transaction already processed by another routine.';
                    return;
                }

                $payoutResult = $ninePsb->payoutStoreToBankDirect(
                    (float) $lockedTx->amount,
                    $request->account_number,
                    $request->bank_code,
                    $verified_account_name,
                    'Employee Auto Payout ID: ' . $lockedTx->id
                );

                \Log::info('EMPLOYEE WITHDRAW - PAYOUT RESPONSE', $payoutResult ?? []);

                if ($payoutResult['success']) {
                    // Update transaction status to approved/success
                    $lockedTx->status = 'approved'; 
                    $lockedTx->reason = 'Auto-paid via 9PSB to ' . $verified_account_name . ' on ' . now()->toDateTimeString();
                    $lockedTx->save();

                    // CRITICAL FIX: Direct atomic decrement chain
                    DB::table('vendor_employees')
                        ->where('id', $lockedTx->employee_id)
                        ->decrement('wallet_balance', $lockedTx->amount);

                    $payoutSuccess = true;
                } else {
                    $payoutMessage = $payoutResult['message'] ?? 'Payout execution rejected by gateway.';
                    
                    // Mark transaction failed so balance isn't tied up indefinitely
                    $lockedTx->status = 'failed';
                    $lockedTx->reason = 'Failed: ' . $payoutMessage;
                    $lockedTx->save();
                }
            });

            if ($payoutSuccess) {
                return back()->with('success', __('Withdrawal processed and transferred successfully.'));
            } else {
                return back()->with('error', __('Payout failed: ') . $payoutMessage);
            }

        } catch (\Exception $e) {
            \Log::error('EMPLOYEE 9PSB RUNTIME EXCEPTION', [
                'tx_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            // Fail gracefully in case of absolute structural code crash
            EmployeeWalletTransaction::where('id', $transaction->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'reason' => 'System error: ' . $e->getMessage()
                ]);

            return back()->with('error', __('An unexpected connectivity error occurred during payout processing.'));
        }
    }

    public function withdraw_cancel($id)
    {
        $employee = auth('vendor_employee')->user();

        $transaction = EmployeeWalletTransaction::where('id', $id)
            ->where('employee_id', $employee->id)
            ->where('type', 'withdraw')
            ->where('status', 'pending')
            ->firstOrFail();

        $transaction->delete();

        return back()->with(
            'success',
            __('Withdrawal request cancelled successfully.')
        );
    }

    public function wallet_method(Request $request)
    {
        $withdrawal_methods = WithdrawalMethod::where('is_active', 1)->get();

        $vendor_withdrawal_methods = collect([]);

        return view(
            'vendor-views.employee-wallet.wallet-method-index',
            compact(
                'withdrawal_methods',
                'vendor_withdrawal_methods'
            )
        );
    }

    public function get_banks(Request $request)
    {
        return (new NinePsbPaymentController)->getBanks($request);
    }

    public function name_enquiry(Request $request)
    {
        return (new NinePsbPaymentController)->nameEnquiry($request);
    }
}