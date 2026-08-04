<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class OrderEmployeePickController extends Controller
{
    public function assign(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        if (!auth('vendor_employee')->check()) {
            Toastr::warning(translate('Only vendor employees can assign orders.'));
            return back();
        }

        $employeeId = auth('vendor_employee')->id();
        $storeId = Helpers::get_store_id();
        $orderId = (int) $request->id;

        $order = Order::where(['id' => $orderId, 'store_id' => $storeId])->first();

        if (!$order) {
            Toastr::error(translate('messages.Order_not_found'));
            return back();
        }

        if ($order->order_status === 'pending') {
            return $this->pickPendingOrder($orderId, $storeId, $employeeId);
        }

        if (in_array($order->order_status, ['confirmed', 'accepted'], true)) {
            return $this->assignConfirmedOrder($orderId, $storeId, $employeeId);
        }

        Toastr::warning(translate('Order unavailable'));
        return back();
    }

    private function pickPendingOrder(int $orderId, int $storeId, int $employeeId)
    {
        $updated = Order::where('id', $orderId)
            ->where('store_id', $storeId)
            ->where('order_status', 'pending')
            ->whereNull('assigned_employee_id')
            ->update([
                'assigned_employee_id' => $employeeId,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            Toastr::warning(translate('Order unavailable'));
            return back();
        }

        Toastr::success(translate('Order picked and assigned to you.'));
        return back();
    }

    private function assignConfirmedOrder(int $orderId, int $storeId, int $employeeId)
    {
        $updated = Order::where('id', $orderId)
            ->where('store_id', $storeId)
            ->whereIn('order_status', ['confirmed', 'accepted'])
            ->whereNull('assigned_employee_id')
            ->update([
                'assigned_employee_id' => $employeeId,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            Toastr::warning(translate('Order unavailable'));
            return back();
        }

        Toastr::success(translate('Order assigned to you. Proceed to cooking to lock it in.'));
        return back();
    }
}
