<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorEmployee;
use App\Models\EmployeeWalletTransaction;
use Illuminate\Http\Request;

class EmployeeWalletController extends Controller
{
    public function index(Request $request)
    {
        $store_id = \App\CentralLogics\Helpers::get_store_id();

        $employees = VendorEmployee::with('role')
            ->where('store_id', $store_id)
            ->get();

        $debit_records = EmployeeWalletTransaction::with('employee.role')
            ->where('store_id', $store_id)
            ->when($request->search, fn($q) =>
                $q->whereHas('employee', fn($q2) =>
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
        $validated = $request->validate([
            'employee_id' => 'required|exists:vendor_employees,id',
            'amount'      => 'required|numeric|min:0.01',
            'reason'      => 'required|string',
            'note'        => 'nullable|string|max:500',
        ]);

        $store_id = \App\CentralLogics\Helpers::get_store_id();

        $employee = VendorEmployee::where('id', $request->employee_id)
            ->where('store_id', $store_id)
            ->first();

        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => __('Employee not found or does not belong to your store.'),
            ], 404);
        }

        if ($employee->wallet_balance < $request->amount) {
            return response()->json([
                'status'  => 'error',
                'message' => __('Insufficient balance. Employee only has ') .
                    \App\CentralLogics\Helpers::format_currency($employee->wallet_balance),
            ], 422);
        }

        $employee->decrement('wallet_balance', $request->amount);

        EmployeeWalletTransaction::create([
            'employee_id' => $request->employee_id,
            'store_id'    => $store_id,
            'amount'      => $request->amount,
            'reason'      => $request->reason,
            'note'        => $request->note,
            'type'        => 'debit',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => __('Wallet debited successfully.'),
        ], 200);
    }
}