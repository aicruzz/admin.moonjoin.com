<?php

namespace App\Http\Controllers\Admin;

use App\Models\DeliveryMan;
use App\Models\DebitDeliveryMan;
use App\Models\DebitDeliverymanReason;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;

class DebitDeliveryManController extends Controller
{
    public function index(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $debit_records = DebitDeliveryMan::with('delivery_man')
            ->when(!empty($key), function ($query) use ($key) {
                return $query->whereHas('delivery_man', function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()
            ->paginate(config('default_pagination'));

        // Preload all reasons as id => reason_text map (avoids per-row DB hits in blade)
        $debit_reasons = DebitDeliverymanReason::pluck('reason', 'id');

        return view('admin-views.debit-delivery-man.index', compact('debit_records', 'debit_reasons'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deliveryman_id' => 'required',
            'amount' => 'required|numeric|min:0.01|max:999999999999.99',
            // FIX: was 'required|string|max:255' — reason is an integer ID from the select
            'reason' => 'required|integer|exists:debit_deliveryman_reasons,id',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $dm = DeliveryMan::findOrFail($request['deliveryman_id']);

        $current_balance = $dm->wallet ? $dm->wallet->balance : 0;

        if (round($current_balance, 2) < round($request['amount'], 2)) {
            $validator->getMessageBag()->add('amount', translate('messages.insufficient_balance'));
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $debit = new DebitDeliveryMan();
        $debit->delivery_man_id = $dm->id;
        $debit->amount = $request['amount'];
        // FIX: cast to int so it stores a clean integer ID, not a string
        $debit->reason = (int) $request['reason'];
        $debit->note = $request['note'] ?? null;

        try {
            DB::beginTransaction();

            $debit->save();

            $dm->wallet->decrement('total_earning', $request['amount']);

            // FIX: now correctly looks up by integer ID
            $reason_text = DebitDeliverymanReason::find((int) $request['reason'])?->reason
                ?? $request['reason'];

            $this->sendDebitNotification($dm, $request['amount'], $reason_text);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => [['code' => 'error', 'message' => $e->getMessage()]]]);
        }

        return response()->json(200);
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $debit_records = DebitDeliveryMan::with('delivery_man')
            ->whereHas('delivery_man', function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%");
                }
            })
            ->latest()
            ->get();

        return response()->json([
            'view' => view('admin-views.debit-delivery-man.partials._table', compact('debit_records'))->render(),
            'total' => $debit_records->count(),
        ]);
    }

    private function sendDebitNotification(DeliveryMan $dm, $amount, string $reason): void
    {
        try {
            $data = [
                'title' => translate('messages.account_debited'),
                'description' => translate('messages.your_account_has_been_debited_of') . ' '
                    . Helpers::format_currency($amount) . ' '
                    . translate('messages.for') . ': ' . $reason,
                'order_id' => '',
                'trip_id' => '',
                'image' => '',
                'type' => 'debit',
                'data_id' => '',
                'status' => '',
            ];

            if ($dm->fcm_token) {
                Helpers::send_push_notif_to_device($dm->fcm_token, $data);
            }

            DB::table('user_notifications')->insert([
                'data' => json_encode($data),
                'delivery_man_id' => $dm->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            info('[DebitDeliveryMan] Notification error: ' . $e->getMessage());
        }
    }
}