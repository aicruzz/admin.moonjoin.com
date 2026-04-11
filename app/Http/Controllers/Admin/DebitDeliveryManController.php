<?php

namespace App\Http\Controllers\Admin;

use App\Models\DeliveryMan;
use App\Models\DebitDeliveryMan;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;

class DebitDeliveryManController extends Controller
{
    /**
     * Display the debit delivery man form and history list.
     */
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

        return view('admin-views.debit-delivery-man.index', compact('debit_records'));
    }

    /**
     * Store a newly created debit record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deliveryman_id' => 'required',
            'amount'         => 'required|numeric|min:0.01|max:999999999999.99',
            'reason'         => 'required|string|max:255',
            'note'           => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $dm = DeliveryMan::findOrFail($request['deliveryman_id']);

        // Use the wallet balance (total_earning - total_withdrawn - pending_withdraw - collected_cash)
        $current_balance = $dm->wallet ? $dm->wallet->balance : 0;

        if (round($current_balance, 2) < round($request['amount'], 2)) {
            $validator->getMessageBag()->add('amount', translate('messages.insufficient_balance'));
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $debit = new DebitDeliveryMan();
        $debit->delivery_man_id = $dm->id;
        $debit->amount          = $request['amount'];
        $debit->reason          = $request['reason'];
        $debit->note            = $request['note'] ?? null;

        try {
            DB::beginTransaction();

            $debit->save();

            // Deduct from total_earning so balance reduces
            $dm->wallet->decrement('total_earning', $request['amount']);

            // Send push notification + save in-app notification for the delivery man
            $this->sendDebitNotification($dm, $request['amount'], $request['reason']);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => [['code' => 'error', 'message' => $e->getMessage()]]]);
        }

        return response()->json(200);
    }

    /**
     * Search debit records (AJAX).
     */
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
            'view'  => view('admin-views.debit-delivery-man.partials._table', compact('debit_records'))->render(),
            'total' => $debit_records->count(),
        ]);
    }

    /**
     * Send push notification and save in-app notification for the delivery man.
     */
    private function sendDebitNotification(DeliveryMan $dm, $amount, string $reason): void
    {
        try {
            $data = [
                'title'       => translate('messages.account_debited'),
                'description' => translate('messages.your_account_has_been_debited_of') . ' '
                                . Helpers::format_currency($amount) . ' '
                                . translate('messages.for') . ': ' . $reason,
                'order_id'    => '',
                'trip_id'     => '',
                'image'       => '',
                'type'        => 'debit',
                'data_id'     => '',
                'status'      => '',
            ];

            // Send push notification if DM has an FCM token
            if ($dm->fcm_token) {
                Helpers::send_push_notif_to_device($dm->fcm_token, $data);
            }

            // Save in-app notification
            DB::table('user_notifications')->insert([
                'data'            => json_encode($data),
                'delivery_man_id' => $dm->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

        } catch (\Exception $e) {
            info('[DebitDeliveryMan] Notification error: ' . $e->getMessage());
        }
    }
}