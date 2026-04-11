<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Cart;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\CartModificationRequest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;

class WebVendorCartController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Helpers::get_vendor_data();
        if (!$vendor || !isset($vendor->stores[0])) {
            Toastr::error('Store not found');
            return back();
        }
        $store = $vendor->stores[0];
        
        $carts = Cart::with(['user', 'item'])
            ->where('store_id', $store->id)
            ->get()
            ->groupBy('user_id');

        return view('vendor-views.cart-manager.index', compact('carts', 'store'));
    }

    public function viewCustomerCart($userId)
    {
        $vendor = Helpers::get_vendor_data();
        if (!$vendor || !isset($vendor->stores[0])) {
            Toastr::error('Store not found');
            return back();
        }
        
        $store = $vendor->stores[0];
        $user = User::find($userId);

        if (!$user) {
            Toastr::error('User not found');
            return back();
        }

        $cartItems = Cart::with('item')->where('user_id', $userId)
            ->where('store_id', $store->id)
            ->get();
            
        $storeItems = Item::where('store_id', $store->id)
            ->where('status', 1)
            ->get();

        return view('vendor-views.cart-manager.view', compact('user', 'cartItems', 'storeItems', 'store'));
    }

    public function removeItem($id)
    {
        $cart = Cart::with('item')->findOrFail($id);
        $refundAmount = $cart->price * $cart->quantity;
        $user = User::query()->find($cart->user_id);
        $itemName = $cart->item ? $cart->item->name : 'Item';
        
        DB::beginTransaction();
        try {
            if ($user && $refundAmount > 0) {
                $status = \App\CentralLogics\CustomerLogic::create_wallet_transaction($user->id, $refundAmount, 'order_refund', 'Vendor removed item from cart: ' . $itemName);
                if (!$status) {
                    throw new \Exception('Wallet transaction failed');
                }
            }

            $cart->delete();
            DB::commit();

            // Notify customer about wallet refund
            if ($user && $refundAmount > 0) {
                $this->sendWalletChangeNotification($user, 'credit', $refundAmount, 'Item removed from cart: ' . $itemName);
            }

            Toastr::success('Item removed and refunded');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error removing item: ' . $e->getMessage());
        }
        return back();
    }

    public function updateQuantity(Request $request)
    {
        $cart = Cart::with('item')->findOrFail($request->cart_id);
        $oldTotal = $cart->price * $cart->quantity;
        $newTotal = $cart->price * $request->quantity;
        $difference = $newTotal - $oldTotal;
        $itemName = $cart->item ? $cart->item->name : 'Item';

        DB::beginTransaction();
        try {
            $cart->quantity = $request->quantity;
            $cart->save();

            if ($difference > 0) {
                $status = \App\CentralLogics\CustomerLogic::create_wallet_transaction($cart->user_id, $difference, 'order_place', 'Vendor updated quantity for: ' . $itemName);
                if (!$status) {
                    throw new \Exception('Wallet charge failed');
                }
            } elseif ($difference < 0) {
                $status = \App\CentralLogics\CustomerLogic::create_wallet_transaction($cart->user_id, abs($difference), 'order_refund', 'Vendor updated quantity for: ' . $itemName);
                if (!$status) {
                    throw new \Exception('Wallet refund failed');
                }
            }

            DB::commit();

            // Notify customer about wallet change
            $user = User::find($cart->user_id);
            if ($user && $difference != 0) {
                $type = $difference > 0 ? 'debit' : 'credit';
                $this->sendWalletChangeNotification($user, $type, abs($difference), 'Cart quantity updated for: ' . $itemName);
            }

            Toastr::success('Quantity updated and wallet adjusted');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error updating quantity: ' . $e->getMessage());
        }
        return back();
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
            'consent_checkbox' => 'required'
        ]);

        $item = Item::findOrFail($request->item_id);
        $vendor = Helpers::get_vendor_data();
        $store = $vendor->stores[0];

        // Ensure item belongs to vendor's store
        if ($item->store_id != $store->id) {
            Toastr::error('Unauthorized access to product');
            return back();
        }

        $price = $request->price ?? $item->price;
        $totalCost = $price * $request->quantity;

        DB::beginTransaction();
        try {
            $cart = new Cart();
            $cart->user_id = $request->user_id;
            $cart->item_id = $request->item_id;
            $cart->item_type = 'App\Models\Item';
            $cart->price = $price;
            $cart->quantity = $request->quantity;
            $cart->store_id = $store->id;
            $cart->module_id = $store->module_id;
            $cart->save();

            // Charge the wallet (even if it goes negative)
            if ($totalCost > 0) {
                $status = \App\CentralLogics\CustomerLogic::create_wallet_transaction($request->user_id, $totalCost, 'order_place', 'Vendor added item to cart: ' . $item->name);
                if (!$status) {
                    throw new \Exception('Wallet transaction failed');
                }
            }

            DB::commit();

            // Notify customer about wallet charge
            $user = User::find($request->user_id);
            if ($user && $totalCost > 0) {
                $this->sendWalletChangeNotification($user, 'debit', $totalCost, 'Item added to cart: ' . $item->name);
            }

            Toastr::success('Item added and charged from wallet');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to add item and charge wallet');
        }
        return back();
    }

    public function getCartItemDetails(Request $request)
    {
        $cart = Cart::with(['item'])->find($request->id);
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'Cart item not found'], 404);
        }

        return response()->json([
            'success' => true,
            'cart' => $cart,
            'item' => $cart->item
        ]);
    }

    public function updateCartItem(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $cart = Cart::with('item')->findOrFail($request->cart_id);
        $oldTotal = $cart->price * $cart->quantity;
        $newTotal = $request->price * $request->quantity;
        $difference = $newTotal - $oldTotal;
        $itemName = $cart->item ? $cart->item->name : 'Item';

        DB::beginTransaction();
        try {
            $cart->quantity = $request->quantity;
            $cart->price = $request->price;
            $cart->save();

            if ($difference > 0) {
                // Price increased: Charge the wallet
                $status = \App\CentralLogics\CustomerLogic::create_wallet_transaction($cart->user_id, $difference, 'order_place', 'Vendor increased item price/qty: ' . $itemName);
                if (!$status) {
                    throw new \Exception('Wallet charge failed');
                }
            } elseif ($difference < 0) {
                // Price decreased: Refund to wallet
                $status = \App\CentralLogics\CustomerLogic::create_wallet_transaction($cart->user_id, abs($difference), 'order_refund', 'Vendor decreased item price/qty: ' . $itemName);
                if (!$status) {
                    throw new \Exception('Wallet refund failed');
                }
            }

            DB::commit();

            // Notify customer about wallet change
            $user = User::find($cart->user_id);
            if ($user && $difference != 0) {
                $type = $difference > 0 ? 'debit' : 'credit';
                $this->sendWalletChangeNotification($user, $type, abs($difference), 'Cart item updated: ' . $itemName);
            }

            Toastr::success('Cart updated and wallet adjusted');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to update cart and adjust wallet: ' . $e->getMessage());
        }

        return back();
    }

    public function notifyCustomer($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            Toastr::error('User not found');
            return back();
        }

        if (!$user->cm_firebase_token) {
            Toastr::warning('Customer does not have a registered device for push notifications');
            return back();
        }

        $notification_data = [
            'title' => translate('messages.cart_re-edited_by_vendor'),
            'description' => translate('Your cart has been re-edited. Please review the changes and ensure your wallet balance is sufficient.'),
            'order_id' => '',
            'image' => '',
            'type' => 'cart_update',
        ];

        try {
            Helpers::send_push_notif_to_device($user->cm_firebase_token, $notification_data);
            
            DB::table('user_notifications')->insert([
                'data' => json_encode($notification_data),
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Toastr::success('Notification sent to customer');
        } catch (\Exception $e) {
            info($e->getMessage());
            Toastr::error('Failed to send push notification');
        }

        return back();
    }

    /**
     * Send push notification to customer about wallet balance change
     */
    private function sendWalletChangeNotification($user, $type, $amount, $reason)
    {
        if (!$user->cm_firebase_token) {
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
            'order_id' => '',
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
            info('Wallet notification failed: ' . $e->getMessage());
        }
    }
}
