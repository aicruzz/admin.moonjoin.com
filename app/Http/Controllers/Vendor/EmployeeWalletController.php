<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorEmployee;
use App\Models\EmployeeWalletTransaction;
use App\Http\Controllers\Api\V1\NinePsbPaymentController;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;

class EmployeeWalletController extends Controller
{
    public function index(Request $request)
    {
        $store_id = \App\CentralLogics\Helpers::get_store_id();

        // ── Employee view — personal wallet only ────────────────
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

            $withdrawable_balance = max(0, ($employee->wallet_balance ?? 0) - $pending_withdraw);

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

        // ── Store owner view — manage all employees' wallets ────
        $employees = VendorEmployee::with('role')
            ->where('store_id', $store_id)
            ->get();

        $debit_records = EmployeeWalletTransaction::with('employee.role')
            ->where('store_id', $store_id)
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

        return view('vendor-views.employee-wallet.index', compact('employees', 'debit_records'));
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
                'message' => __('Employee not found or does not belong to your store.'),
            ], 404);
        }

        if ($employee->wallet_balance < $request->amount) {
            return response()->json([
                'status' => 'error',
                'message' => __('Insufficient balance. Employee only has ') .
                    \App\CentralLogics\Helpers::format_currency($employee->wallet_balance),
            ], 422);
        }

        $employee->decrement('wallet_balance', $request->amount);

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
            'message' => __('Wallet debited successfully.'),
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

        $store_id = \App\CentralLogics\Helpers::get_store_id();
        $employee = auth('vendor_employee')->user();

        $pending_withdraw = EmployeeWalletTransaction::where('employee_id', $employee->id)
            ->where('store_id', $store_id)
            ->where('type', 'withdraw')
            ->where('status', 'pending')
            ->sum('amount');

        $withdrawable_balance = max(0, ($employee->wallet_balance ?? 0) - $pending_withdraw);

        if ($request->amount > $withdrawable_balance) {
            return back()->with('error', __('Insufficient withdrawable balance.'));
        }

        EmployeeWalletTransaction::create([
            'employee_id' => $employee->id,
            'store_id' => $store_id,
            'amount' => $request->amount,
            'type' => 'withdraw',
            'status' => 'pending',
            'reason' => 'Withdrawal Request',
            'withdrawal_method_fields' => json_encode([
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank_code' => $request->bank_code,
                'bank_name' => $request->bank_name,
            ]),
        ]);

        return back()->with('success', __('Withdrawal request submitted successfully.'));
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

        return back()->with('success', __('Withdrawal request cancelled successfully.'));
    }

    public function wallet_method(Request $request)
    {
        $withdrawal_methods = WithdrawalMethod::where('is_active', 1)->get();
        $vendor_withdrawal_methods = collect([]);

        return view('vendor-views.employee-wallet.wallet-method-index', compact(
            'withdrawal_methods',
            'vendor_withdrawal_methods'
        ));
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