<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\OrderCancelReason;
use App\Models\Store;
use App\Models\Coupon;
use App\Exports\OrderExport;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\OrderPayment;
use App\Models\OrderDetail;
use App\Models\ItemCampaign;
use App\Scopes\StoreScope;
use App\Traits\PlaceNewOrder;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\CentralLogics\OrderLogic;
use App\CentralLogics\CouponLogic;
use App\CentralLogics\ProductLogic;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\StoreWallet;
use App\Models\VendorEmployee;
use App\Models\DisbursementWithdrawalMethod;
use App\Models\WithdrawRequest;
use App\Http\Controllers\Api\V1\NinePsbPaymentController;


class OrderController extends Controller
{
    use PlaceNewOrder;

    /**
     * B.6: order lifecycle states in which a vendor payout is financially valid.
     *
     * Explicit allow-list, never a deny-list: order_status is an unconstrained
     * varchar, so any unknown or future value must fail closed. out_for_delivery
     * is deliberately absent - it has no timestamp column and nothing in this
     * installation ever assigns it.
     */
    private const PAYOUT_ELIGIBLE_ORDER_STATUSES = ['processing', 'handover', 'picked_up', 'delivered'];
    public function list($status)
    {
        $key = explode(' ', request()?->search);
        Order::where(['checked' => 0])->where('store_id', Helpers::get_store_id())->update(['checked' => 1]);

        $orders = Order::with(['customer'])
            ->when($status == 'searching_for_deliverymen', function ($query) {
                return $query->SearchingForDeliveryman();
            })
            ->when($status == 'confirmed', function ($query) {
                return $query->whereIn('order_status', ['confirmed', 'accepted'])->whereNotNull('confirmed');
            })
            ->when($status == 'pending', function ($query) {
                if (config('order_confirmation_model') == 'store' || Helpers::get_store_data()->sub_self_delivery) {
                    return $query->where('order_status', 'pending');
                } else {
                    return $query->where('order_status', 'pending')->where('order_type', 'take_away');
                }
            })
            ->when($status == 'cooking', function ($query) {
                return $query->where('order_status', 'processing');
            })
            ->when($status == 'item_on_the_way', function ($query) {
                return $query->where('order_status', 'picked_up');
            })
            ->when($status == 'delivered', function ($query) {
                return $query->Delivered();
            })
            ->when($status == 'ready_for_delivery', function ($query) {
                return $query->where('order_status', 'handover');
            })
            ->when($status == 'refund_requested', function ($query) {
                return $query->RefundRequest();
            })
            ->when($status == 'refunded', function ($query) {
                return $query->Refunded();
            })
            ->when($status == 'scheduled', function ($query) {
                return $query->Scheduled()->where(function ($q) {
                    if (config('order_confirmation_model') == 'store' || Helpers::get_store_data()->sub_self_delivery) {
                        $q->whereNotIn('order_status', ['failed', 'canceled', 'refund_requested', 'refunded']);
                    } else {
                        $q->whereNotIn('order_status', ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])->orWhere(function ($query) {
                            $query->where('order_status', 'pending')->where('order_type', 'take_away');
                        });
                    }

                });
            })
            ->when($status == 'all', function ($query) {
                return $query->where(function ($query) {
                    $query->whereNotIn('order_status', (config('order_confirmation_model') == 'store' || Helpers::get_store_data()->sub_self_delivery) ? ['failed', 'canceled', 'refund_requested', 'refunded'] : ['accepted', 'failed', 'canceled', 'refund_requested', 'refunded'])
                        ->orWhere('order_status', 'pending');
                });
            })
            ->when(in_array($status, ['pending', 'confirmed']), function ($query) {
                return $query->OrderScheduledIn(30);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            })
            ->StoreOrder()->NotDigitalOrder()
            ->where('store_id', \App\CentralLogics\Helpers::get_store_id())
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));
        $status = $status;
        return view('vendor-views.order.list', compact('orders', 'status'));
    }

    public function export_orders($file_type, $status, $type, Request $request)
    {
        $key = explode(' ', request()?->search);
        Order::where(['checked' => 0])->where('store_id', Helpers::get_store_id())->update(['checked' => 1]);
        $orders = Order::with(['customer'])
            ->when($status == 'searching_for_deliverymen', function ($query) {
                return $query->SearchingForDeliveryman();
            })
            ->when($status == 'confirmed', function ($query) {
                return $query->whereIn('order_status', ['confirmed', 'accepted'])->whereNotNull('confirmed');
            })
            ->when($status == 'pending', function ($query) {
                if (config('order_confirmation_model') == 'store' || Helpers::get_store_data()->sub_self_delivery) {
                    return $query->where('order_status', 'pending');
                } else {
                    return $query->where('order_status', 'pending')->where('order_type', 'take_away');
                }
            })
            ->when($status == 'cooking', function ($query) {
                return $query->where('order_status', 'processing');
            })
            ->when($status == 'item_on_the_way', function ($query) {
                return $query->where('order_status', 'picked_up');
            })
            ->when($status == 'delivered', function ($query) {
                return $query->Delivered();
            })
            ->when($status == 'ready_for_delivery', function ($query) {
                return $query->where('order_status', 'handover');
            })
            ->when($status == 'refund_requested', function ($query) {
                return $query->RefundRequest();
            })
            ->when($status == 'refunded', function ($query) {
                return $query->Refunded();
            })
            ->when($status == 'scheduled', function ($query) {
                return $query->Scheduled()->where(function ($q) {
                    if (config('order_confirmation_model') == 'store' || Helpers::get_store_data()->sub_self_delivery) {
                        $q->whereNotIn('order_status', ['failed', 'canceled', 'refund_requested', 'refunded']);
                    } else {
                        $q->whereNotIn('order_status', ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])->orWhere(function ($query) {
                            $query->where('order_status', 'pending')->where('order_type', 'take_away');
                        });
                    }

                });
            })
            ->when($status == 'all', function ($query) {
                return $query->where(function ($query) {
                    $query->whereNotIn('order_status', (config('order_confirmation_model') == 'store' || Helpers::get_store_data()->sub_self_delivery) ? ['failed', 'canceled', 'refund_requested', 'refunded'] : ['accepted', 'failed', 'canceled', 'refund_requested', 'refunded'])
                        ->orWhere('order_status', 'pending');
                });
            })
            ->when(in_array($status, ['pending', 'confirmed']), function ($query) {
                return $query->OrderScheduledIn(30);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            })
            ->StoreOrder()->NotDigitalOrder()
            ->where('store_id', \App\CentralLogics\Helpers::get_store_id())
            ->orderBy('schedule_at', 'desc')
            ->get();

        $data = [
            'orders' => $orders,
            'type' => $type,
            'status' => $status,
            'order_status' => isset($request->orderStatus) ? implode(', ', $request->orderStatus) : null,
            'search' => $request->search ?? null,
            'from' => $request->from_date ?? null,
            'to' => $request->to_date ?? null,
            'zones' => isset($request->zone) ? Helpers::get_zones_name($request->zone) : null,
            'stores' => isset($request->vendor) ? Helpers::get_stores_name(Helpers::get_store_id()) : null,
        ];

        if ($file_type == 'excel') {
            return Excel::download(new OrderExport($data), 'Orders.xlsx');
        } else if ($file_type == 'csv') {
            return Excel::download(new OrderExport($data), 'Orders.csv');
        }

    }

    public function details(Request $request, $id)
    {
        $order = Order::with([
            'details',
            'offline_payments',
            'store' => function ($query) {
                return $query->withCount('orders');
            },
            'customer' => function ($query) {
                return $query->withCount('orders');
            },
            'delivery_man' => function ($query) {
                return $query->withCount('orders');
            },
            'details.item' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            },
            'details.campaign' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            }
        ])->where(['id' => $id, 'store_id' => Helpers::get_store_id()])->first();
        if (isset($order)) {
            $category = $request->query('category_id', 0);
            $categories = Category::active()->get();
            $keyword = $request->query('keyword', false);
            $key = explode(' ', $keyword);
            $products = Item::where('store_id', $order->store_id)
                ->when($category, function ($query) use ($category) {
                    $query->whereHas('category', function ($q) use ($category) {
                        return $q->whereId($category)->orWhere('parent_id', $category);
                    });
                })
                ->when($keyword, function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%");
                        }
                    });
                })
                ->latest()->active()->paginate(10);

            $editing = false;
            if ($request->session()->has('order_cart')) {
                $cart = $request->session()->get('order_cart');
                if (count($cart) > 0 && $cart[0]->order_id == $id) {
                    $editing = true;
                } else {
                    $request->session()->forget('order_cart');
                }
            }

            $isUnpaid = false;

            if (
                in_array($order->order_status, ['pending', 'failed']) &&
                !in_array($order->payment_method, ['cash_on_delivery', 'wallet'])
            ) {
                // CASE 1: partial payment
                if ($order->payment_method == 'partial_payment') {
                    $isUnpaid = $order->payments()
                        ->where('payment_status', 'unpaid')
                        ->whereNotIn('payment_method', ['cash_on_delivery', 'wallet'])
                        ->exists();
                }

                // CASE 2: offline payment
                elseif ($order->payment_method == 'offline_payment') {
                    if ($order?->offline_payments?->count() == 0) {
                        $isUnpaid = true;
                    }
                }

                // CASE 3: other online payments
                else {
                    $isUnpaid = true;
                }
            }

            $order->is_unpaid_order = $isUnpaid ? true : false;

            $reasons = OrderCancelReason::where('status', 1)->where('user_type', 'store')->get();
            return view('vendor-views.order.order-view', compact('order', 'reasons', 'editing', 'categories', 'products', 'category', 'keyword'));
        } else {
            Toastr::info('No more orders!');
            return back();
        }
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'order_status' => 'required|in:confirmed,processing,handover,delivered,canceled',
            'reason' => 'required_if:order_status,canceled',
        ], [
            'id.required' => 'Order id is required!'
        ]);

        $order = Order::where(['id' => $request->id, 'store_id' => Helpers::get_store_id()])->first();

        if ($order->delivered != null) {
            Toastr::warning(translate('messages.cannot_change_status_after_delivered'));
            return back();
        }

        if ($request['order_status'] == 'canceled' && !config('canceled_by_store')) {
            Toastr::warning(translate('messages.you_can_not_cancel_a_order'));
            return back();
        }

        if ($request['order_status'] == 'canceled' && $order->confirmed) {
            Toastr::warning(translate('messages.you_can_not_cancel_after_confirm'));
            return back();
        }



        if ($request['order_status'] == 'delivered' && $order->order_type != 'take_away' && !Helpers::get_store_data()->sub_self_delivery) {
            Toastr::warning(translate('messages.you_can_not_delivered_delivery_order'));
            return back();
        }

        // Enforce claim + pay before handover
        if ($request['order_status'] == 'handover') {
            if ($order->claim_status !== 'claimed') {
                Toastr::warning(translate('Please claim order funds before marking ready for handover.'));
                return back();
            }
            if ($order->pay_status !== 'paid') {
                Toastr::warning(translate('Please complete the payout before marking ready for handover.'));
                return back();
            }
        }

        if ($request['order_status'] == 'confirmed' && auth('vendor_employee')->check()) {
            $employee = auth('vendor_employee')->user();
            if ($order->assigned_employee_id && $order->assigned_employee_id != $employee->id) {
                Toastr::warning(translate('Order unavailable'));
                return back();
            }
        }

        // Enforce assignment and max active orders before cooking
        if ($request['order_status'] == 'processing' && auth('vendor_employee')->check()) {
            $employee = auth('vendor_employee')->user();

            // Block if another employee has the soft assignment
            if ($order->assigned_employee_id && $order->assigned_employee_id != $employee->id) {
                Toastr::warning(translate('This order is assigned to another employee. Ask them to release it first.'));
                return back();
            }

            // Check max active orders limit
            $maxActive = (int) (BusinessSetting::where('key', 'max_active_orders_per_employee')->first()?->value ?? 0);
            if ($maxActive > 0) {
                $activeCount = Order::where('locked_employee_id', $employee->id)
                    ->whereIn('order_status', ['processing', 'handover'])
                    ->count();
                if ($activeCount >= $maxActive) {
                    Toastr::warning(translate('You have reached your maximum active order limit (' . $maxActive . '). Complete an existing order first.'));
                    return back();
                }
            }

            // Lock the order to this employee
            $order->locked_employee_id = $employee->id;
            $order->assigned_employee_id = $employee->id;
        }

        if ($request['order_status'] == "confirmed") {
            if (!Helpers::get_store_data()->sub_self_delivery && config('order_confirmation_model') == 'deliveryman' && $order->order_type != 'take_away') {
                Toastr::warning(translate('messages.order_confirmation_warning'));
                return back();
            }
        }

        if ($request->order_status == 'delivered') {
            $order_delivery_verification = (boolean) \App\Models\BusinessSetting::where(['key' => 'order_delivery_verification'])->first()->value;
            if ($order_delivery_verification) {
                if ($request->otp) {
                    if ($request->otp != $order->otp) {
                        Toastr::warning(translate('messages.order_varification_code_not_matched'));
                        return back();
                    }
                } else {
                    Toastr::warning(translate('messages.order_varification_code_is_required'));
                    return back();
                }
            }

            if ($order->transaction == null) {
                $unpaid_payment = OrderPayment::where('payment_status', 'unpaid')->where('order_id', $order->id)->first()?->payment_method;
                $unpaid_pay_method = 'digital_payment';
                if ($unpaid_payment) {
                    $unpaid_pay_method = $unpaid_payment;
                }
                if ($order->payment_method == 'cash_on_delivery' || $unpaid_pay_method == 'cash_on_delivery') {
                    $ol = OrderLogic::create_transaction($order, 'store', null);
                } else {
                    $ol = OrderLogic::create_transaction($order, 'admin', null);
                }
                if (!$ol) {
                    Toastr::warning(translate('messages.faield_to_create_order_transaction'));
                    return back();
                }
                if ($order->delivery_man_id) {
                    Helpers::deliverymanLoyaltyPointHistory(deliveryManId: $order->delivery_man_id, amount: $order->order_amount, transactionType: 'earn_on_order_completion', pointConversionType: 'credit', reference: $order->id);
                }

            }

            $order->payment_status = 'paid';

            OrderLogic::update_unpaid_order_payment(order_id: $order->id, payment_method: $order->payment_method);

            $order->details->each(function ($item, $key) {
                if ($item->item) {
                    $item->item->increment('order_count');
                }
            });
            if ($order->is_guest == 0) {
                $order?->customer?->increment('order_count');
            }
        }
        if ($request->order_status == 'canceled' || $request->order_status == 'delivered') {
            if ($order->delivery_man) {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders > 1 ? $dm->current_orders - 1 : 0;
                $dm->save();
            }
            if ($request->order_status == 'canceled') {

                $order->cancellation_reason = $request->reason;
                $order->canceled_by = 'store';

                $order?->store ? Helpers::increment_order_count($order?->store) : '';

            }

            if ($order->is_guest == 0) {

                OrderLogic::refund_before_delivered($order);
            }

        }

        if ($request->order_status == 'delivered') {
            $order->store->increment('order_count');
            if ($order->delivery_man) {
                $order->delivery_man->increment('order_count');
            }

        }

        $order->order_status = $request->order_status;
        if ($request->order_status == 'processing') {
            $order->processing_time = ($request?->processing_time) ? $request->processing_time : explode('-', $order['store']['delivery_time'])[0];
        } else if ($order->order_type != 'parcel' && in_array($request->order_status, ['picked_up'])) {
            Helpers::sendOrderDeliveryVerificationOtp($order);
        }

        $order[$request['order_status']] = now();
        $order->save();
        if (!Helpers::send_order_notification($order)) {
            Toastr::warning(translate('messages.push_notification_faild'));
        }

        Toastr::success(translate('messages.order_status_updated'));
        return back();
    }

    public function update_shipping(Request $request, $id)
    {
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required'
        ]);

        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'floor' => $request->floor,
            'road' => $request->road,
            'house' => $request->house,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('customer_addresses')->where('id', $id)->update($address);
        Toastr::success('Delivery address updated!');
        return back();
    }

    public function generate_invoice($id)
    {
        $order = Order::where(['id' => $id, 'store_id' => Helpers::get_store_id()])->first();
        return view('vendor-views.order.invoice', compact('order'));
    }

    public function add_payment_ref_code(Request $request, $id)
    {
        Order::where(['id' => $id, 'store_id' => Helpers::get_store_id()])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success('Payment reference code is added!');
        return back();
    }

    public function edit_order_amount(Request $request)
    {

        // Financial freeze: the settlement figure is fixed once funds are claimed.
        // Runs before validation, the status gate and every recalculation, so no
        // settlement-affecting value can change after claim. This lookup does not
        // depend on the validator below, which only checks order_amount.
        $frozen_order = Order::find($request->order_id);
        if ($frozen_order && $frozen_order->claim_status === 'claimed') {
            Toastr::error(translate('messages.order_is_financially_locked_after_claim'));
            return back();
        }

        $request->validate([
            'order_amount' => 'required',

        ]);

        $order = Order::find($request->order_id);
        if (!$order) {
            Toastr::error(translate('messages.Order_not_found'));
            return back();
        }

        if (!in_array($order->order_status, ['pending', 'confirmed', 'processing', 'picked_up', 'handover', 'accepted'])) {
            Toastr::error(translate('messages.Order_can_not_edit_a_completed_order'));
            return back();
        }
        $store = Store::find($order->store_id);
        $coupon = null;
        $free_delivery_by = null;
        if ($order->coupon_code) {
            $coupon = Coupon::active()->where(['code' => $order->coupon_code])->first();
            if (isset($coupon)) {
                $staus = CouponLogic::is_valide($coupon, $order->user_id, $order->store_id);
                if ($staus == 407) {
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.coupon_expire')]
                        ]
                    ], 407);
                } else if ($staus == 406) {
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.coupon_usage_limit_over')]
                        ]
                    ], 406);
                } else if ($staus == 404) {
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.not_found')]
                        ]
                    ], 404);
                }
            } else {
                return response()->json([
                    'errors' => [
                        ['code' => 'coupon', 'message' => translate('messages.not_found')]
                    ]
                ], 404);
            }
        }

        $product_price = $request->order_amount;
        $total_addon_price = 0;
        $store_discount_amount = $order->store_discount_amount;

        // $discount=$order->store_discount_amount;
        $discount_on_product_by = $order->discount_on_product_by ?? 'vendor';

        $store_discount = Helpers::get_store_discount($store);
        $store_discount = $store_discount ? $store_discount : ['discount' => 0, 'max_discount' => 0, 'min_purchase' => 0];
        $admin_discount = Helpers::checkAdminDiscount(price: $product_price + $total_addon_price, discount: $store_discount['discount'], max_discount: $store_discount['max_discount'], min_purchase: $store_discount['min_purchase']);

        $discount = $admin_discount;

        if ($admin_discount > 0 && $discount == $admin_discount) {
            $discount_on_product_by = 'admin';
        }


        $order->discount_on_product_by = $discount_on_product_by;
        $store_discount_amount = $discount;
        $additionalCharges = [];


        $coupon_discount_amount = $coupon ? CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount) : 0;
        $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount;
        $total_price = max($total_price, 0);
        //Added service charge


        $settings = BusinessSetting::whereIn('key', [
            'dm_tips_status',
            'additional_charge_status',
            'additional_charge',
            'extra_packaging_data',
        ])->pluck('value', 'key');

        $dm_tips_manage_status = $settings['dm_tips_status'] ?? null;
        $additional_charge_status = $settings['additional_charge_status'] ?? null;
        $additional_charge = $settings['additional_charge'] ?? null;

        $extra_packaging_data_raw = $settings['extra_packaging_data'] ?? '';
        $extra_packaging_data = json_decode($extra_packaging_data_raw, true) ?? [];


        //Added DM TIPS
        if ($dm_tips_manage_status == 1) {
            $order->dm_tips = $order->dm_tips ?? $request->dm_tips ?? 0;
        } else {
            $order->dm_tips = 0;
        }

        //Added service charge
        $order->additional_charge = $order->additional_charge;

        if ($additional_charge_status == 1) {
            $order->additional_charge = $additional_charge ?? 0;
            // $additionalCharges['tax_on_additional_charge'] = $order->additional_charge;
        }

        // extra packaging charge

        // $order->extra_packaging_amount =  (!empty($extra_packaging_data) && $request?->extra_packaging_amount > 0 && $store && ($extra_packaging_data[$store->module->module_type] == '1') && ($store?->storeConfig?->extra_packaging_status == '1')) ? $store?->storeConfig?->extra_packaging_amount : 0;

        // if ($order->extra_packaging_amount > 0) {
        //     $additionalCharges['tax_on_packaging_charge'] =  $order->extra_packaging_amount;
        // }

        $taxData = \Modules\TaxModule\Services\CalculateTaxService::getCalculatedTax(
            amount: $total_price,
            productIds: [],
            taxPayer: 'prescription',
            storeData: true,
            additionalCharges: $additionalCharges,
            addonIds: [],
            orderId: null,
            storeId: $store->id
        );

        $tax_amount = $taxData['totalTaxamount'];
        $tax_included = $taxData['include'];
        $orderTaxIds = $taxData['orderTaxIds'] ?? [];
        $tax_status = $tax_included ? 'included' : 'excluded';

        $order->total_tax_amount = round($tax_amount, config('round_up_to_digit'));
        $order->tax_status = $tax_status;

        $free_delivery_over = BusinessSetting::where('key', 'free_delivery_over')->first()->value;
        if (isset($free_delivery_over)) {
            if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $store_discount_amount) {
                $order->delivery_charge = 0;
                $free_delivery_by = 'admin';
            }
        }

        if ($store->free_delivery) {
            $order->delivery_charge = 0;
            $free_delivery_by = 'vendor';
        }

        if ($coupon) {
            if ($coupon->coupon_type == 'free_delivery') {
                if ($coupon->min_purchase <= $product_price + $total_addon_price - $store_discount_amount) {
                    $order->delivery_charge = 0;
                    $free_delivery_by = 'admin';
                }
            }
            // $coupon->increment('total_uses');
        }

        $order->coupon_discount_amount = round($coupon_discount_amount, config('round_up_to_digit'));
        $order->coupon_discount_title = $coupon ? $coupon->title : '';

        $order->store_discount_amount = round($store_discount_amount, config('round_up_to_digit'));
        $new_order_amount = round($total_price + $order->total_tax_amount + $order->additional_charge + $order->delivery_charge, config('round_up_to_digit')) + $order->dm_tips;

        if (($order->payment_method == 'wallet' || $order->payment_status == 'paid') && $new_order_amount > $order->order_amount) {
            $diff = $new_order_amount - $order->order_amount;
            if ($order->customer && $order->customer->wallet_balance < $diff) {
                $notification_data = [
                    'title' => translate('messages.insufficient_wallet_balance'),
                    'description' => translate('messages.your_wallet_balance_is_insufficient_to_cover_the_additional_amount_of_') . Helpers::format_currency($diff) . translate('messages._please_fund_your_wallet_to_process_this_order_edit.'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                if ($order->customer->cm_firebase_token) {
                    Helpers::send_push_notif_to_device($order->customer->cm_firebase_token, $notification_data);

                    DB::table('user_notifications')->insert([
                        'data' => json_encode($notification_data),
                        'user_id' => $order->user_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                Toastr::error(translate('messages.customer_has_insufficient_wallet_balance'));
                return back();
            }
        }

        $old_order_amount = $order->order_amount;
        $order->order_amount = $new_order_amount;
        $order->free_delivery_by = $free_delivery_by;
        $order->save();

        $difference = $order->order_amount - $old_order_amount;
        if ($difference != 0 && ($order->payment_method == 'wallet' || $order->payment_status == 'paid')) {
            if ($difference > 0) {
                \App\CentralLogics\CustomerLogic::create_wallet_transaction($order->user_id, $difference, 'order_place', 'Order amount increased for order ID: ' . $order->id);
            } else if ($difference < 0) {
                \App\CentralLogics\CustomerLogic::create_wallet_transaction($order->user_id, abs($difference), 'order_refund', 'Order amount decreased for order ID: ' . $order->id);
            }

            // Notify customer about wallet change
            $type = $difference > 0 ? 'debit' : 'credit';
            $reason = $difference > 0 ? 'Order amount increased for order #' . $order->id : 'Order amount decreased for order #' . $order->id;
            $this->sendOrderWalletChangeNotification($order->user_id, $type, abs($difference), $reason, $order->id);
        }
        $order?->orderTaxes()?->delete();
        if (count($orderTaxIds)) {
            \Modules\TaxModule\Services\CalculateTaxService::updateOrderTaxData(
                orderId: $order->id,
                orderTaxIds: $orderTaxIds,
            );
        }
        Toastr::success(translate('messages.order_amount_updated'));
        return back();
    }
    public function edit_discount_amount(Request $request)
    {
        // Financial freeze: no discount change after funds are claimed. Runs
        // before validation and before any recalculation.
        $frozen_order = Order::find($request->order_id);
        if ($frozen_order && $frozen_order->claim_status === 'claimed') {
            Toastr::error(translate('messages.order_is_financially_locked_after_claim'));
            return back();
        }

        $request->validate([
            'discount_amount' => 'required',

        ]);

        $order = Order::find($request->order_id);
        if (!$order) {
            Toastr::error(translate('messages.Order_not_found'));
            return back();
        }

        if (!in_array($order->order_status, ['pending', 'confirmed', 'processing', 'picked_up', 'handover', 'accepted'])) {
            Toastr::error(translate('messages.Order_can_not_edit_a_completed_order'));
            return back();
        }
        $product_price = $order['order_amount'] - $order['delivery_charge'] - $order['total_tax_amount'] - $order['dm_tips'] - $order->additional_charge + $order->store_discount_amount;


        if ($request->discount_amount > $product_price) {
            Toastr::error(translate('messages.discount_amount_is_greater_then_product_amount'));
            return back();
        }
        $order->store_discount_amount = round($request->discount_amount, config('round_up_to_digit'));

        $order->discount_on_product_by = 'vendor';

        $settings = BusinessSetting::whereIn('key', [
            'dm_tips_status',
            'additional_charge_status',
            'additional_charge',
            'extra_packaging_data',
        ])->pluck('value', 'key');

        $dm_tips_manage_status = $settings['dm_tips_status'] ?? null;
        $additional_charge_status = $settings['additional_charge_status'] ?? null;
        $additional_charge = $settings['additional_charge'] ?? null;

        $extra_packaging_data_raw = $settings['extra_packaging_data'] ?? '';
        $extra_packaging_data = json_decode($extra_packaging_data_raw, true) ?? [];


        //Added DM TIPS
        if ($dm_tips_manage_status == 1) {
            $order->dm_tips = $order->dm_tips ?? $request->dm_tips ?? 0;
        } else {
            $order->dm_tips = 0;
        }

        //Added service charge
        $order->additional_charge = $order->additional_charge;

        if ($additional_charge_status == 1) {
            $order->additional_charge = $additional_charge ?? 0;
            // $additionalCharges['tax_on_additional_charge'] = $order->additional_charge;
        }

        // // extra packaging charge

        // $order->extra_packaging_amount =  (!empty($extra_packaging_data) && $request?->extra_packaging_amount > 0 && $store && ($extra_packaging_data[$store->module->module_type] == '1') && ($store?->storeConfig?->extra_packaging_status == '1')) ? $store?->storeConfig?->extra_packaging_amount : 0;

        // if ($order->extra_packaging_amount > 0) {
        //     $additionalCharges['tax_on_packaging_charge'] =  $order->extra_packaging_amount;
        // }



        $taxData = \Modules\TaxModule\Services\CalculateTaxService::getCalculatedTax(
            amount: $product_price - $request->discount_amount,
            productIds: [],
            taxPayer: 'prescription',
            storeData: true,
            additionalCharges: [],
            addonIds: [],
            orderId: null,
            storeId: $order->store_id
        );

        $tax_amount = $taxData['totalTaxamount'];
        $tax_included = $taxData['include'];
        $orderTaxIds = $taxData['orderTaxIds'] ?? [];
        $tax_status = $tax_included ? 'included' : 'excluded';

        $order->total_tax_amount = round($tax_amount, config('round_up_to_digit'));
        $order->tax_status = $tax_status;



        $new_order_amount = $product_price + $order['delivery_charge'] + $order->total_tax_amount + $order['dm_tips'] + $order->additional_charge - $order->store_discount_amount;

        if (($order->payment_method == 'wallet' || $order->payment_status == 'paid') && $new_order_amount > $order->order_amount) {
            $diff = $new_order_amount - $order->order_amount;
            if ($order->customer && $order->customer->wallet_balance < $diff) {
                $notification_data = [
                    'title' => translate('messages.insufficient_wallet_balance'),
                    'description' => translate('messages.your_wallet_balance_is_insufficient_to_cover_the_additional_amount_of_') . Helpers::format_currency($diff) . translate('messages._please_fund_your_wallet_to_process_this_order_edit.'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                if ($order->customer->cm_firebase_token) {
                    Helpers::send_push_notif_to_device($order->customer->cm_firebase_token, $notification_data);

                    DB::table('user_notifications')->insert([
                        'data' => json_encode($notification_data),
                        'user_id' => $order->user_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                Toastr::error(translate('messages.customer_has_insufficient_wallet_balance'));
                return back();
            }
        }

        $old_order_amount = $order->order_amount;
        $order->order_amount = $new_order_amount;
        $order->save();

        $difference = $order->order_amount - $old_order_amount;
        if ($difference != 0 && ($order->payment_method == 'wallet' || $order->payment_status == 'paid')) {
            if ($difference > 0) {
                \App\CentralLogics\CustomerLogic::create_wallet_transaction($order->user_id, $difference, 'order_place', 'Order discount edited (amount increased) for order ID: ' . $order->id);
            } else if ($difference < 0) {
                \App\CentralLogics\CustomerLogic::create_wallet_transaction($order->user_id, abs($difference), 'order_refund', 'Order discount edited (amount decreased) for order ID: ' . $order->id);
            }

            // Notify customer about wallet change
            $type = $difference > 0 ? 'debit' : 'credit';
            $reason = $difference > 0 ? 'Discount edited (amount increased) for order #' . $order->id : 'Discount edited (amount decreased) for order #' . $order->id;
            $this->sendOrderWalletChangeNotification($order->user_id, $type, abs($difference), $reason, $order->id);
        }
        $order?->orderTaxes()?->delete();
        if (count($orderTaxIds)) {
            \Modules\TaxModule\Services\CalculateTaxService::updateOrderTaxData(
                orderId: $order->id,
                orderTaxIds: $orderTaxIds,
            );
        }
        Toastr::success(translate('messages.discount_amount_updated'));
        return back();
    }

    public function add_order_proof(Request $request, $id)
    {
        $order = Order::find($id);
        $img_names = $order->order_proof ? json_decode($order->order_proof) : [];
        $images = [];
        $total_file = count($request->order_proof) + count($img_names);
        if (!$img_names) {
            $request->validate([
                'order_proof' => 'required|array|max:5',
            ]);
        }

        if ($total_file > 5) {
            Toastr::error(translate('messages.order_proof_must_not_have_more_than_5_item'));
            return back();
        }

        if (!empty($request->file('order_proof'))) {
            foreach ($request->order_proof as $img) {
                $image_name = Helpers::upload('order/', 'png', $img);
                array_push($img_names, ['img' => $image_name, 'storage' => Helpers::getDisk()]);
            }
            $images = $img_names;
        }

        if (count($images) > 0) {
            $order->order_proof = json_encode($images);
        }
        $order->save();

        Toastr::success(translate('messages.order_proof_added'));
        return back();
    }

    public function remove_proof_image(Request $request)
    {
        $order = Order::find($request['id']);
        $array = [];
        $proof = isset($order->order_proof) ? json_decode($order->order_proof, true) : [];
        if (count($proof) < 2) {
            Toastr::warning(translate('all_image_delete_warning'));
            return back();
        }

        Helpers::check_and_delete('order/', $request['name']);

        foreach ($proof as $image) {
            if ($image != $request['name']) {
                array_push($array, $image);
            }
        }
        Order::where('id', $request['id'])->update([
            'order_proof' => json_encode($array),
        ]);
        Toastr::success(translate('order_proof_image_removed_successfully'));
        return back();
    }

    public function add_to_cart(Request $request)
    {
        if ($request->item_type == 'item') {
            $product = Item::find($request->id);
        } else {
            $product = ItemCampaign::find($request->id);
        }

        if (isset($product->module_id) && $product->module->module_type == 'food' && $product->food_variations) {
            $data = new OrderDetail();
            if ($request->order_details_id) {
                $data['id'] = $request->order_details_id;
            }

            $data['item_id'] = $request->item_type == 'item' ? $product->id : null;
            $data['item_campaign_id'] = $request->item_type == 'campaign' ? $product->id : null;
            $data['item'] = $request->item_type == 'item' ? $product : null;
            $data['item_campaign'] = $request->item_type == 'campaign' ? $product : null;
            $data['order_id'] = $request->order_id;
            $variations = [];
            $price = 0;
            $addon_price = 0;
            $variation_price = 0;

            $product_variations = json_decode($product->food_variations, true);
            if ($request->variations && $product_variations && count($product_variations)) {
                foreach ($request->variations as $key => $value) {

                    if ($value['required'] == 'on' && isset($value['values']) == false) {
                        return response()->json([
                            'data' => 'variation_error',
                            'message' => translate('Please select items from') . ' ' . $value['name'],
                        ]);
                    }
                    if (isset($value['values']) && $value['min'] != 0 && $value['min'] > count($value['values']['label'])) {
                        return response()->json([
                            'data' => 'variation_error',
                            'message' => translate('Please select minimum ') . $value['min'] . translate('For') . $value['name'] . '.',
                        ]);
                    }
                    if (isset($value['values']) && $value['max'] != 0 && $value['max'] < count($value['values']['label'])) {
                        return response()->json([
                            'data' => 'variation_error',
                            'message' => translate('Please select maximum ') . $value['max'] . translate('For') . $value['name'] . '.',
                        ]);
                    }
                }
                $variation_data = Helpers::get_varient($product_variations, $request->variations);
                $variation_price = $variation_data['price'];
                $variations = $variation_data['variations'];
            }
            $price = $product->price + $variation_price;
            $data['variation'] = json_encode($variations);
            $data['variant'] = '';
            // $data['variation_price'] = $variation_price;
            $data['quantity'] = $request['quantity'];
            $data['price'] = $price;
            $data['status'] = true;
            $data['discount_on_item'] = Helpers::product_discount_calculate($product, $price, $product->store)['discount_amount'];
            $data["discount_type"] = "discount_on_product";
            $data["tax_amount"] = Helpers::tax_calculate($product, $price);
            $add_ons = [];
            $add_on_qtys = [];

            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                    $add_on_qtys[] = $request['addon-quantity' . $id];
                }
                $add_ons = $request['addon_id'];
            }

            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::withOutGlobalScope(StoreScope::class)->whereIn('id', $add_ons)->get(), $add_on_qtys);
            $data['add_ons'] = json_encode($addon_data['addons']);
            $data['total_add_on_price'] = $addon_data['total_add_on_price'];
            $cart = $request->session()->get('order_cart', collect([]));

            if (isset($request->cart_item_key)) {
                $cart[$request->cart_item_key] = $data;

                $this->setOrderEditCalculatedTax(store: $product->store, order_id: $request->order_id);

                return response()->json([
                    'data' => 2
                ]);
            } else {
                $cart->push($data);
                $this->setOrderEditCalculatedTax(store: $product->store, order_id: $request->order_id);

            }

        } else {

            $data = new OrderDetail();
            if ($request->order_details_id) {
                $data['id'] = $request->order_details_id;
            }

            $data['item_id'] = $request->item_type == 'item' ? $product->id : null;
            $data['item_campaign_id'] = $request->item_type == 'campaign' ? $product->id : null;
            $data['order_id'] = $request->order_id;
            $str = '';
            $price = 0;
            $addon_price = 0;

            //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
            foreach (json_decode($product->choice_options) as $key => $choice) {
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request[$choice->name]);
                } else {
                    $str .= str_replace(' ', '', $request[$choice->name]);
                }
            }
            $data['variant'] = json_encode([]);
            $data['variation'] = json_encode([]);
            if ($request->session()->has('order_cart') && !isset($request->cart_item_key)) {
                if (count($request->session()->get('order_cart')) > 0) {
                    foreach ($request->session()->get('order_cart') as $key => $cartItem) {
                        // dd($cartItem);
                        if ($cartItem && $cartItem['item_id'] == $request['id'] && $cartItem['status'] == true) {
                            if (count(json_decode($cartItem['variation'], true)) > 0) {
                                if (json_decode($cartItem['variation'], true)[0]['type'] == $str) {
                                    return response()->json([
                                        'data' => 1
                                    ]);
                                }
                            } else {
                                return response()->json([
                                    'data' => 1
                                ]);
                            }
                        }
                    }
                }
            }
            //Check the string and decreases quantity for the stock
            if ($str != null) {
                $count = count(json_decode($product->variations));
                for ($i = 0; $i < $count; $i++) {
                    if (json_decode($product->variations)[$i]->type == $str) {
                        $vr = json_decode($product->variations);
                        $price = $vr[$i]->price;
                        $stock = isset($vr[$i]->stock) ? $vr[$i]->stock : 0;
                    }
                }
                $data['variation'] = json_encode([["type" => $str, "price" => $price, "stock" => $stock]]);
            } else {
                $price = $product->price;
            }

            $data['quantity'] = $request['quantity'];
            $data['price'] = $price;
            $data['status'] = true;
            $data['discount_on_item'] = Helpers::product_discount_calculate($product, $price, $product->store)['discount_amount'];
            $data["discount_type"] = "discount_on_product";
            $data["tax_amount"] = Helpers::tax_calculate($product, $price);
            $add_ons = [];
            $add_on_qtys = [];

            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                    $add_on_qtys[] = $request['addon-quantity' . $id];
                }
                $add_ons = $request['addon_id'];
            }

            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::withoutGlobalScope(StoreScope::class)->whereIn('id', $add_ons)->get(), $add_on_qtys);
            $data['add_ons'] = json_encode($addon_data['addons']);
            $data['total_add_on_price'] = $addon_data['total_add_on_price'];


            $cart = $request->session()->get('order_cart', collect([]));
            if (isset($request->cart_item_key)) {
                $cart[$request->cart_item_key] = $data;
                $this->setOrderEditCalculatedTax(store: $product->store, order_id: $request->order_id);
                return response()->json([
                    'data' => 2
                ]);
            } else {
                $this->setOrderEditCalculatedTax(store: $product->store, order_id: $request->order_id);
                $cart->push($data);
            }
        }

        $this->setOrderEditCalculatedTax(store: $product->store, order_id: $request->order_id);

        return response()->json([
            'data' => 0
        ]);
    }

    public function remove_from_cart(Request $request)
    {
        $cart = $request->session()->get('order_cart', collect([]));
        $item_id = $cart[$request->key]['item_id'];
        $cart[$request->key]->status = false;
        $request->session()->put('order_cart', $cart);

        $product = Item::withoutGlobalScope(StoreScope::class)->with('store')->find($item_id);

        if ($product && $product->store) {
            $this->setOrderEditCalculatedTax(store: $product->store, order_id: $request->order_id);
        }
        return response()->json([], 200);
    }

    public function edit(Request $request, Order $order)
    {
        $order = Order::with([
            'details',
            'store' => function ($query) {
                return $query->withCount('orders');
            },
            'customer' => function ($query) {
                return $query->withCount('orders');
            },
            'delivery_man' => function ($query) {
                return $query->withCount('orders');
            },
            'details.item' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            },
            'details.campaign' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            }
        ])->where(['id' => $order->id, 'store_id' => Helpers::get_store_id()])->first();

        if ($request->cancle) {
            if ($request->session()->has(['order_cart'])) {
                session()->forget(['order_cart']);
            }
            return back();
        }
        $cart = collect([]);
        foreach ($order->details as $details) {
            unset($details['item_details']);
            $details['status'] = true;
            $cart->push($details);
        }

        if ($request->session()->has('order_cart')) {
            session()->forget('order_cart');
        } else {
            $request->session()->put('order_cart', $cart);
            $this->setOrderEditCalculatedTax(store: $order->store, order_id: $order->id);
        }
        return back();
    }

    public function update(Request $request, Order $order)
    {
        // Financial freeze: the settlement figure is fixed once funds are claimed.
        // First statement in the method, so it precedes the eager-load, every
        // validation, all recalculation and the order_details rewrite below.
        if ($order->claim_status === 'claimed') {
            Toastr::error(translate('messages.order_is_financially_locked_after_claim'));
            return back();
        }

        $order = Order::with([
            'details',
            'store' => function ($query) {
                return $query->withCount('orders');
            },
            'customer' => function ($query) {
                return $query->withCount('orders');
            },
            'delivery_man' => function ($query) {
                return $query->withCount('orders');
            },
            'details.item' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            },
            'details.campaign' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            }
        ])->where(['id' => $order->id, 'store_id' => Helpers::get_store_id()])->first();

        if (!$request->session()->has('order_cart')) {
            Toastr::error(translate('messages.order_data_not_found'));
            return back();
        }
        DB::beginTransaction();
        $cart = $request->session()->get('order_cart', collect([]));
        $store = $order->store;
        $coupon = null;
        $total_addon_price = 0;
        $product_price = 0;
        $store_discount_amount = 0;
        if ($order->coupon_code) {
            $coupon = Coupon::where(['code' => $order->coupon_code])->first();
        }

        foreach ($cart as $c) {
            try {
                if ($c['status'] == true) {
                    if ($c['item_campaign_id'] != null) {
                        $product = ItemCampaign::find($c['item_campaign_id']);
                        if ($product) {
                            $price = $c['price'];
                            $product = Helpers::product_data_formatting($product);
                            $c->item_details = json_encode($product);
                            $c->updated_at = now();
                            if (isset($c->id)) {
                                OrderDetail::where('id', $c->id)->update([
                                    'item_id' => $c->item_id,
                                    'item_campaign_id' => $c->item_campaign_id,
                                    'item_details' => $c->item_details,
                                    'quantity' => $c->quantity,
                                    'price' => $c->price,
                                    'tax_amount' => $c->tax_amount,
                                    'discount_on_item' => $c->discount_on_item * $c->quantity,
                                    'discount_on_product_by' => $request->session()->has('discount_on_product_by_session') ? $request->session()->get('discount_on_product_by_session') : $c?->discount_on_product_by,
                                    'discount_type' => $c->discount_type,
                                    'variant' => $c->variant,
                                    'variation' => $c->variation,
                                    'add_ons' => $c->add_ons,
                                    'total_add_on_price' => $c->total_add_on_price,
                                    'updated_at' => $c->updated_at
                                ]);
                            } else {
                                $status = $c['status'];
                                unset($c['status']);
                                $c->save();
                                $c['status'] = $status;
                            }
                            $order_details_ids[] = $c->id;
                            $total_addon_price += $c['total_add_on_price'];
                            $product_price += $price * $c['quantity'];
                            $store_discount_amount += $c['discount_on_item'] * $c['quantity'];
                        } else {
                            DB::rollBack();
                            Toastr::error(translate('messages.item_not_found'));
                            return back();
                        }
                    } else {
                        $product = Item::find($c['item_id']);
                        if ($product) {
                            $price = $c['price'];
                            $product = Helpers::product_data_formatting($product);
                            $c->item_details = json_encode($product);
                            $c->updated_at = now();
                            if (isset($c->id)) {
                                OrderDetail::where('id', $c->id)->update([
                                    'item_id' => $c->item_id,
                                    'item_campaign_id' => $c->item_campaign_id,
                                    'item_details' => $c->item_details,
                                    'quantity' => $c->quantity,
                                    'price' => $c->price,
                                    'tax_amount' => $c->tax_amount,
                                    'discount_on_item' => $c->discount_on_item * $c->quantity,
                                    'discount_on_product_by' => $request->session()->has('discount_on_product_by_session') ? $request->session()->get('discount_on_product_by_session') : $c?->discount_on_product_by,
                                    'discount_type' => $c->discount_type,
                                    'variant' => $c->variant,
                                    'variation' => $c->variation,
                                    'add_ons' => $c->add_ons,
                                    'total_add_on_price' => $c->total_add_on_price,
                                    'updated_at' => $c->updated_at
                                ]);
                            } else {
                                $status = $c['status'];
                                $item = isset($c['item']) ? $c['item'] : null;
                                $campaign = isset($c['item_campaign']) ? $c['item_campaign'] : null;
                                unset($c['status']);
                                unset($c['item']);
                                unset($c['item_campaign']);
                                $c->save();
                                $c['status'] = $status;
                                if ($item)
                                    $c['item'] = $item;
                                if ($campaign)
                                    $c['item_campaign'] = $campaign;
                            }
                            $order_details_ids[] = $c->id;
                            $total_addon_price += $c['total_add_on_price'];
                            $product_price += $price * $c['quantity'];
                            $store_discount_amount += $c['discount_on_item'] * $c['quantity'];
                        } else {
                            DB::rollBack();
                            Toastr::error(translate('messages.item_not_found'));
                            return back();
                        }
                    }
                } else {
                    $c->delete();
                }
            } catch (\Throwable $th) {
                info($th->getMessage());
            }
        }

        $store_discount = Helpers::get_store_discount($store);
        if (isset($store_discount)) {
            if ($product_price + $total_addon_price < $store_discount['min_purchase']) {
                $store_discount_amount = 0;
            }
            if ($store_discount_amount > $store_discount['max_discount'] && $store_discount_amount > $store_discount['max_discount']) {
                $store_discount_amount = $store_discount['max_discount'];
            }
        }

        $order->delivery_charge = $order->original_delivery_charge;
        if ($coupon) {
            if ($coupon->coupon_type == 'free_delivery') {
                $order->delivery_charge = 0;
                $coupon = null;
            }
        }

        if ($order->store->free_delivery || $order->order_type == 'take_away') {
            $order->delivery_charge = 0;
        }

        $additionalCharges = [];
        $settings = BusinessSetting::whereIn('key', [
            'additional_charge_status',
            'additional_charge',
            'extra_packaging_data',
        ])->pluck('value', 'key');

        $additional_charge_status = $settings['additional_charge_status'] ?? null;
        $additional_charge = $settings['additional_charge'] ?? null;

        $order->additional_charge = 0;
        if ($additional_charge_status == 1) {
            $order->additional_charge = $additional_charge ?? 0;
        }

        $order_details_result = $this->makeEditOrderDetails($cart, null, $store);

        // ✅ FIX 1: Check for error response BEFORE accessing order_details
        if (data_get($order_details_result, 'status_code') === 403) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    ['code' => data_get($order_details_result, 'code'), 'message' => data_get($order_details_result, 'message')]
                ]
            ], 403);
        }

        // ✅ FIX 2: Guard against missing order_details key before iterating
        if (!isset($order_details_result['order_details'])) {
            DB::rollBack();
            Toastr::error(translate('messages.order_data_not_found'));
            return back();
        }

        foreach ($order_details_result['order_details'] as $key => $order_de) {
            $order->details()->where('id', $order_de['cart_id'])->update([
                'discount_on_item' => $order_de['discount_on_item'],
                'discount_on_product_by' => $order_de['discount_on_product_by'],
                'discount_type' => $order_de['discount_type'],
            ]);
        }

        $total_addon_price = $order_details_result['total_addon_price'];
        $product_price = $order_details_result['product_price'];
        $store_discount_amount = $order_details_result['store_discount_amount'];
        $flash_sale_admin_discount_amount = $order_details_result['flash_sale_admin_discount_amount'];
        $flash_sale_vendor_discount_amount = $order_details_result['flash_sale_vendor_discount_amount'];
        $product_data = $order_details_result['product_data'];
        $order_details = $order_details_result['order_details'];

        $coupon_discount_amount = $coupon ? CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount) : 0;
        $total_price = $product_price + $total_addon_price - $store_discount_amount - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount - $coupon_discount_amount;
        $totalDiscount = $store_discount_amount + $flash_sale_admin_discount_amount + $flash_sale_vendor_discount_amount + $coupon_discount_amount + $order->ref_bonus_amount;

        $finalCalculatedTax = Helpers::getFinalCalculatedTax($order_details, $additionalCharges, $totalDiscount, $total_price, $store->id);
        $tax_amount = $finalCalculatedTax['tax_amount'];
        $tax_included = $finalCalculatedTax['tax_included'];
        $tax_status = $finalCalculatedTax['tax_status'];
        $taxMap = $finalCalculatedTax['taxMap'];
        $orderTaxIds = data_get($finalCalculatedTax, 'taxData.orderTaxIds', []);
        $taxType = data_get($finalCalculatedTax, 'taxType');

        $order->tax_type = $taxType;
        $order->tax_status = $tax_status;

        $total_tax_amount = $tax_amount;
        $total_tax_amount = $order->tax_status == 'included' ? 0 : $total_tax_amount;

        if ($store->minimum_order > $product_price + $total_addon_price) {
            DB::rollBack();
            Toastr::error(translate('messages.you_need_to_order_at_least', ['amount' => $store->minimum_order . ' ' . Helpers::currency_code()]));
            return back();
        }

        $free_delivery_over = BusinessSetting::where('key', 'free_delivery_over')->first()->value;
        if (isset($free_delivery_over)) {
            if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $store_discount_amount) {
                $order->delivery_charge = 0;
            }
        }

        $total_order_ammount = $total_price + $total_tax_amount + $order->delivery_charge + $order->additional_charge;

        if (($order->payment_method == 'wallet' || $order->payment_status == 'paid') && $total_order_ammount > $order->order_amount) {
            $diff = $total_order_ammount - $order->order_amount;
            if ($order->customer && $order->customer->wallet_balance < $diff) {
                DB::rollBack();
                $notification_data = [
                    'title' => translate('messages.insufficient_wallet_balance'),
                    'description' => translate('messages.your_wallet_balance_is_insufficient_to_cover_the_additional_amount_of_') . Helpers::format_currency($diff) . translate('messages._please_fund_your_wallet_to_process_this_order_edit.'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                if ($order->customer->cm_firebase_token) {
                    Helpers::send_push_notif_to_device($order->customer->cm_firebase_token, $notification_data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($notification_data),
                        'user_id' => $order->user_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                Toastr::error(translate('messages.customer_has_insufficient_wallet_balance'));
                return back();
            }
        }

        $old_order_amount = $order->order_amount;
        $adjustment = $order->order_amount - $total_order_ammount;
        $order->coupon_discount_amount = $coupon_discount_amount;
        $order->store_discount_amount = $store_discount_amount;
        $order->total_tax_amount = $total_tax_amount;
        $order->order_amount = $total_order_ammount;
        $order->adjusment = $adjustment;
        $order->edited = true;
        $order->save();

        $difference = $order->order_amount - $old_order_amount;
        if ($difference != 0 && ($order->payment_method == 'wallet' || $order->payment_status == 'paid')) {
            if ($difference > 0) {
                \App\CentralLogics\CustomerLogic::create_wallet_transaction($order->user_id, $difference, 'order_place', 'Order edited (amount increased) for order ID: ' . $order->id);
            } else if ($difference < 0) {
                \App\CentralLogics\CustomerLogic::create_wallet_transaction($order->user_id, abs($difference), 'order_refund', 'Order edited (amount decreased) for order ID: ' . $order->id);
            }
            $type = $difference > 0 ? 'debit' : 'credit';
            $reason = $difference > 0
                ? 'Order edited (amount increased) for order #' . $order->id
                : 'Order edited (amount decreased) for order #' . $order->id;
            $this->sendOrderWalletChangeNotification($order->user_id, $type, abs($difference), $reason, $order->id);
        }

        if ($order->order_type !== 'parcel') {
            $taxMapCollection = collect($taxMap);
            foreach ($order_details as $key => $item) {
                $order_details[$key]['order_id'] = $order->id;
                $item_id = $item['item_id'] ?: $item['item_campaign_id'];
                $index = $taxMapCollection->search(fn($tax) => $tax['product_id'] == $item_id);
                if ($index !== false) {
                    $matchedTax = $taxMapCollection->pull($index);
                    $order_details[$key]['tax_status'] = $matchedTax['include'] == 1 ? 'included' : 'excluded';
                    $order_details[$key]['tax_amount'] = $matchedTax['totalTaxamount'];
                }
            }

            $order?->orderTaxes()?->delete();
            if (count($orderTaxIds)) {
                \Modules\TaxModule\Services\CalculateTaxService::updateOrderTaxData(
                    orderId: $order->id,
                    orderTaxIds: $orderTaxIds,
                );
            }
            if (count($product_data) > 0) {
                foreach ($product_data as $item) {
                    ProductLogic::update_stock($item['item'], $item['quantity'], $item['variant'])->save();
                    ProductLogic::update_flash_stock($item['item'], $item['quantity'])?->save();
                }
            }
        }

        session()->forget('order_cart');
        session()->forget('edit_tax_amount');
        session()->forget('edit_tax_included');
        DB::commit();
        Toastr::success(translate('messages.order_updated_successfully'));
        return back();
    }

    public function quick_view(Request $request)
    {
        $product = Item::findOrFail($request->product_id);
        $item_type = 'item';
        $order_id = $request->order_id;

        return response()->json([
            'success' => 1,
            'view' => view('vendor-views.order.partials._quick-view', compact('product', 'order_id', 'item_type'))->render(),
        ]);
    }

    public function quick_view_cart_item(Request $request)
    {
        $cart_item = session('order_cart')[$request->key];
        $order_id = $request->order_id;
        $item_key = $request->key;
        $product = $cart_item->item ? $cart_item->item : $cart_item->campaign;
        $item_type = $cart_item->item ? 'item' : 'campaign';

        return response()->json([
            'success' => 1,
            'view' => view('vendor-views.order.partials._quick-view-cart-item', compact('order_id', 'product', 'cart_item', 'item_key', 'item_type'))->render(),
        ]);
    }

    /**
     * Send push notification to customer about wallet balance change due to order edit
     */
    private function sendOrderWalletChangeNotification($user_id, $type, $amount, $reason, $order_id = '')
    {
        $user = User::find($user_id);
        if (!$user || !$user->cm_firebase_token) {
            return;
        }

        $formattedAmount = Helpers::format_currency($amount);

        if ($type === 'credit') {
            $title = translate('messages.wallet_credited');
            $description = $formattedAmount . ' ' . translate('messages.has_been_refunded_to_your_wallet') . '. ' . translate('messages.reason') . ': ' . $reason;
        } else {
            $title = translate('messages.wallet_debited');
            $description = $formattedAmount . ' ' . translate('messages.has_been_charged_from_your_wallet') . '. ' . translate('messages.reason') . ': ' . $reason;
        }

        $notification_data = [
            'title' => $title,
            'description' => $description,
            'order_id' => (string) $order_id,
            'image' => '',
            'type' => 'wallet_change',
        ];

        try {
            Helpers::send_push_notif_to_device($user->cm_firebase_token, $notification_data);

            DB::table('user_notifications')->insert([
                'data' => json_encode($notification_data),
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            info('Order wallet notification failed: ' . $e->getMessage());
        }
    }

    public function markUnavailableItems(Request $request, $id)
    {
        $order = Order::where(['id' => $id, 'store_id' => Helpers::get_store_id()])->first();
        if (!$order) {
            Toastr::error(translate('messages.Order_not_found'));
            return back();
        }

        // Financial freeze: once funds are claimed the settlement figure is fixed,
        // so no new negotiation may reopen it. Only an admin dispute/refund
        // workflow may move money after this point.
        if ($order->claim_status === 'claimed') {
            Toastr::error(translate('messages.order_is_financially_locked_after_claim'));
            return back();
        }

        if (!in_array($order->order_status, ['pending', 'confirmed', 'processing'])) {
            Toastr::error(translate('messages.order_cannot_be_edited_at_this_stage'));
            return back();
        }

        $request->validate([
            'unavailable_item_ids' => 'required|array|min:1',
            'unavailable_item_ids.*' => 'integer',
            'unavailable_note' => 'nullable|string|max:500',
        ]);

        // Build notification payload matching send_push_notif_to_device expectations
        $unavailableCount = count($request->unavailable_item_ids);
        $notification_data = [
            'title' => translate('messages.order_unavailable_items'),
            'description' => translate('messages.you_have') . ' ' . $unavailableCount
                . ' ' . translate('messages.unavailable_items_in_order')
                . ' #' . $order->id
                . '. ' . translate('messages.please_edit_your_order'),
            'order_id' => (string) $order->id,
            'image' => '',
            'type' => 'order_unavailable_items',
            'order_type' => $order->order_type ?? '',
            'status' => $order->order_status,
        ];

        // Negotiation state and the customer's in-app notification are written in a
        // single transaction: a vendor request must never be persisted without the
        // customer being told about it. user_notifications is the same store used by
        // sendOrderWalletChangeNotification() below and by Admin Order Edit.
        try {
            DB::transaction(function () use ($order, $request, $notification_data) {
                $order->unavailable_item_ids = json_encode($request->unavailable_item_ids);
                // The vendor's message goes in its own column. unavailable_item_note
                // is the customer's checkout instruction, written at order placement
                // by Traits\PlaceNewOrder and shown read-only in the app, so the
                // vendor must not overwrite it.
                $order->unavailable_item_vendor_note = $request->unavailable_note ?? null;
                $order->customer_edit_requested = 1;
                $order->save();

                DB::table('user_notifications')->insert([
                    'data' => json_encode($notification_data),
                    'user_id' => $order->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            info('Unavailable items request failed for order #' . $order->id . ': ' . $e->getMessage());
            Toastr::error(translate('messages.failed_to_notify_customer'));
            return back();
        }

        // Push is sent only after the transaction commits: it cannot be rolled back,
        // and a push failure must not discard state the customer can already see
        // in-app. Customers without a firebase token still get the in-app record.
        if ($order->customer && $order->customer->cm_firebase_token) {
            try {
                Helpers::send_push_notif_to_device(
                    $order->customer->cm_firebase_token,
                    $notification_data
                );
            } catch (\Exception $e) {
                info('Unavailable items push notification failed: ' . $e->getMessage());
            }
        }

        Toastr::success(translate('messages.customer_notified_to_edit_order'));
        return redirect(url('vendor-panel/order/details/' . $order->id));
    }

    public function assign_order(Request $request)
    {
        return app(OrderEmployeePickController::class)->assign($request);
    }

    /**
     * May the current actor take a financial action on this order?
     *
     * Owners are never restricted by employee assignment. An employee may act
     * only on an order assigned to them: an order assigned to someone else, and
     * an order with no assignment at all, are both refused. Money movement must
     * always have a responsible owner.
     *
     * Deliberately stricter than status(), where a NULL assignment passes.
     * status() governs order handling; this governs money, and the two rules are
     * intentionally different.
     *
     * Shared by claim and payout so the rule cannot drift between the two
     * financial endpoints.
     */
    private function actorMayActOnOrderFinancially(Order $order): bool
    {
        if (!auth('vendor_employee')->check()) {
            return true;
        }

        $employeeId = auth('vendor_employee')->id();

        return $order->assigned_employee_id !== null
            && (int) $order->assigned_employee_id === (int) $employeeId;
    }

    public function claim_order_funds(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        // Claim is an independent capability. Owners resolve against
        // vendor_permissions, employees against their role -- both via the same
        // entry point, so this controller never branches on actor type.
        if (!Helpers::actor_can('claim')) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }

        $store_id = Helpers::get_store_id();

        // Correlates every write and log line of this one irreversible operation.
        // Carried in references and logs only -- no schema. The durable
        // Financial Operation ID arrives with audit logs and the ledger.
        $operationId = (string) \Illuminate\Support\Str::uuid();

        $outcome = ['level' => 'error', 'message' => translate('messages.Order_not_found')];
        $audit   = null;

        try {
            DB::transaction(function () use ($request, $store_id, $operationId, &$outcome, &$audit) {
                // Lock the order row before reading claim_status. Concurrent claims
                // serialise here, so the second request observes 'claimed' below
                // instead of racing past the check and crediting the wallet twice.
                $order = Order::where(['id' => $request->id, 'store_id' => $store_id])
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    $outcome = ['level' => 'error', 'message' => translate('messages.Order_not_found')];
                    return;
                }

                // Assignment authorization, evaluated against the same locked row
                // that is about to be mutated. Sits above the financial-state
                // guards because it answers who may act, not what state allows.
                if (!$this->actorMayActOnOrderFinancially($order)) {
                    $outcome = ['level' => 'error', 'message' => translate('messages.access_denied')];
                    return;
                }

                if ($order->order_status !== 'processing') {
                    $outcome = ['level' => 'warning', 'message' => translate('Order must be in cooking/processing status to claim funds.')];
                    return;
                }

                // Idempotent: a replay of an already-settled claim reports success
                // without moving money a second time.
                if ($order->claim_status === 'claimed') {
                    $outcome = ['level' => 'warning', 'message' => translate('Funds already claimed for this order.')];
                    return;
                }

                $order->load(['transaction', 'customer']);

                $unpaid_payment = OrderPayment::where('payment_status', 'unpaid')->where('order_id', $order->id)->first()?->payment_method;
                $unpaid_pay_method = $unpaid_payment ?? 'digital_payment';
                $is_cod = $order->payment_method == 'cash_on_delivery' || $unpaid_pay_method == 'cash_on_delivery';

                // Create the order transaction (credits vendor wallet)
                if ($order->transaction === null) {
                    if ($is_cod) {
                        $ol = OrderLogic::create_transaction($order, 'store', null);
                    } else {
                        $ol = OrderLogic::create_transaction($order, 'admin', null);
                    }

                    if (!$ol) {
                        // Throw rather than return: the wallet writes above must be
                        // rolled back, not committed half-done.
                        throw new \RuntimeException('order_transaction_failed');
                    }
                }

                // Debit the customer's 9PSB virtual wallet for non-CoD orders
                if (!$is_cod && $order->customer) {
                    $debitAmount = $order->payment_status === 'partially_paid'
                        ? (float) $order->partially_paid_amount
                        : (float) $order->order_amount;

                    $ninePsb = new NinePsbPaymentController();
                    $debited = $ninePsb->debitCustomer(
                        $order->customer,
                        $debitAmount,
                        "Payment for Order #{$order->id}"
                    );

                    if (!$debited) {
                        \Illuminate\Support\Facades\Log::error('claim_order_funds: 9PSB virtual wallet debit failed, claim rolled back', [
                            'operation_id' => $operationId,
                            'order_id'     => $order->id,
                            'user_id'      => $order->customer->id,
                            'amount'       => $debitAmount,
                        ]);

                        // Settlement failed, so the vendor must not be credited.
                        // Rolls back create_transaction() above.
                        throw new \RuntimeException('psb_debit_failed');
                    }
                }

                $order->claim_status = 'claimed';
                $order->save();

                \Illuminate\Support\Facades\Log::info('claim_order_funds: claim settled', [
                    'operation_id' => $operationId,
                    'order_id'     => $order->id,
                    'store_id'     => $store_id,
                ]);

                $audit = [
                    'before' => ['claim_status' => null],
                    'after'  => ['claim_status' => 'claimed'],
                    'meta'   => [
                        'store_id'     => $store_id,
                        'store_amount' => (float) ($order->transaction?->store_amount ?? 0),
                        'is_cod'       => $is_cod,
                    ],
                ];

                $outcome = ['level' => 'success', 'message' => translate('Order funds claimed successfully.')];
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('claim_order_funds: transaction rolled back', [
                'operation_id' => $operationId,
                'order_id'     => $request->id,
                'reason'       => $e->getMessage(),
            ]);

            $outcome = $e->getMessage() === 'psb_debit_failed'
                ? ['level' => 'error', 'message' => translate('Customer settlement failed. Funds were not claimed.')]
                : ['level' => 'error', 'message' => translate('Failed to create order transaction. Please try again.')];
        }

        // Recorded after the transaction closes. A logging failure must never
        // roll back settled money, so this sits outside the transaction and
        // AuditLog::record() swallows its own errors.
        if ($audit !== null) {
            \App\Models\AuditLog::record(
                action: \App\Models\AuditLog::ACTION_CLAIM_FUNDS,
                subjectType: 'order',
                subjectId: (int) $request->id,
                before: $audit['before'],
                after: $audit['after'],
                requestId: $operationId,
                metadata: $audit['meta'],
            );
        }

        if ($outcome['level'] === 'success') {
            Toastr::success($outcome['message']);
        } elseif ($outcome['level'] === 'warning') {
            Toastr::warning($outcome['message']);
        } else {
            Toastr::error($outcome['message']);
        }

        return back();
    }

    public function pay_vendor_payout(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        // Payout is an independent capability, withheld from owners by default.
        if (!Helpers::actor_can('payout')) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }

        $store_id  = Helpers::get_store_id();
        $vendor_id = Helpers::get_vendor_id();

        // Correlates every write and log line of this one irreversible operation.
        $operationId = (string) \Illuminate\Support\Str::uuid();

        $outcome = ['level' => 'error', 'message' => translate('messages.Order_not_found')];
        $audit   = null;

        DB::transaction(function () use ($request, $store_id, $vendor_id, $operationId, &$outcome, &$audit) {
            // Every guard now runs inside the transaction against locked rows.
            // Previously pay_status, the payout amount and the wallet balance were
            // all read before the transaction opened, leaving a window in which two
            // concurrent requests could both pass and both insert a withdrawal.
            $order = Order::where(['id' => $request->id, 'store_id' => $store_id])
                ->lockForUpdate()
                ->first();

            if (!$order) {
                $outcome = ['level' => 'error', 'message' => translate('messages.Order_not_found')];
                return;
            }

            // Assignment authorization, evaluated against the same locked row
            // that is about to be mutated.
            if (!$this->actorMayActOnOrderFinancially($order)) {
                $outcome = ['level' => 'error', 'message' => translate('messages.access_denied')];
                return;
            }

            // B.6 lifecycle eligibility, evaluated against the locked row.
            // Production held claimed orders reset backwards to pending (order
            // 100002, via switch_to_cod) and claimed orders sitting in terminal
            // states, all of which still passed every payout guard. Payout is
            // valid only while the order is still in a fulfilment state.
            if (!in_array($order->order_status, self::PAYOUT_ELIGIBLE_ORDER_STATUSES, true)) {
                $outcome = ['level' => 'error', 'message' => translate('messages.order_not_eligible_for_payout')];
                return;
            }

            if ($order->claim_status !== 'claimed') {
                $outcome = ['level' => 'warning', 'message' => translate('Please claim order funds before requesting payout.')];
                return;
            }

            // Idempotent: a replay reports the existing payout rather than queuing
            // a second withdrawal.
            if ($order->pay_status === 'paid') {
                $outcome = ['level' => 'warning', 'message' => translate('Payout already requested for this order.')];
                return;
            }

            $order->load('transaction');

            $store_amount = (float) ($order->transaction?->store_amount ?? 0);
            if ($store_amount <= 0) {
                $outcome = ['level' => 'error', 'message' => translate('Invalid payout amount. Please ensure funds are claimed first.')];
                return;
            }

            // Get vendor's default saved payout account
            $disbursementMethod = DisbursementWithdrawalMethod::where('store_id', $store_id)
                ->where('is_default', 1)
                ->first();

            if (!$disbursementMethod) {
                $outcome = ['level' => 'error', 'message' => translate('No default payout account found. Please set up a withdrawal method in your wallet settings.')];
                return;
            }

            // Assign the locked instance. Previously the result of lockForUpdate()
            // was discarded, so the balance check and increment ran against a stale
            // object read before the transaction began.
            $wallet = StoreWallet::where('vendor_id', $vendor_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet || $wallet->balance < $store_amount) {
                $outcome = ['level' => 'error', 'message' => translate('Insufficient wallet balance for payout.')];
                return;
            }

            DB::table('withdraw_requests')->insert([
                'vendor_id'                => $vendor_id,
                'amount'                   => $store_amount,
                'transaction_note'         => 'Order #' . $order->id . ' payout',
                'withdrawal_method_id'     => $disbursementMethod->withdrawal_method_id,
                'withdrawal_method_fields' => $disbursementMethod->withdrawal_method_fields ?? '{}',
                'approved'                 => 0,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);

            $wallet->increment('pending_withdraw', $store_amount);

            $order->pay_status = 'paid';
            $order->save();

            \Illuminate\Support\Facades\Log::info('pay_vendor_payout: payout queued', [
                'operation_id' => $operationId,
                'order_id'     => $order->id,
                'vendor_id'    => $vendor_id,
                'amount'       => $store_amount,
            ]);

            $audit = [
                'before' => ['pay_status' => null],
                'after'  => ['pay_status' => 'paid'],
                'meta'   => [
                    'vendor_id'            => $vendor_id,
                    'store_id'             => $store_id,
                    'amount'               => $store_amount,
                    'withdrawal_method_id' => $disbursementMethod->withdrawal_method_id,
                ],
            ];

            $outcome = ['level' => 'success', 'message' => translate('Payout requested successfully.')];
        });

        // Recorded after the transaction closes, for the same reason as claim.
        if ($audit !== null) {
            \App\Models\AuditLog::record(
                action: \App\Models\AuditLog::ACTION_PAYOUT,
                subjectType: 'order',
                subjectId: (int) $request->id,
                before: $audit['before'],
                after: $audit['after'],
                requestId: $operationId,
                metadata: $audit['meta'],
            );
        }

        if ($outcome['level'] === 'success') {
            Toastr::success($outcome['message']);
        } elseif ($outcome['level'] === 'warning') {
            Toastr::warning($outcome['message']);
        } else {
            Toastr::error($outcome['message']);
        }

        return back();
    }
}

