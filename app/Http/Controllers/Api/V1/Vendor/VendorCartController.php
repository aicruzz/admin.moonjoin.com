<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Models\Cart;
use App\Models\Item;
use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\WalletTransaction;
use App\Http\Controllers\Controller;
use App\Models\CartModificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorCartController extends Controller
{
    /**
     * Get all customer carts containing items from the vendor's store
     */
    public function getCustomerCarts(Request $request)
    {
        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $carts = Cart::with(['user', 'store'])
            ->where('store_id', $store->id)
            ->where('is_guest', 0) // Only registered users
            ->get()
            ->groupBy('user_id')
            ->map(function ($userCarts, $userId) {
                $user = User::find($userId);
                return [
                    'user_id' => $userId,
                    'user_name' => $user ? $user->f_name . ' ' . $user->l_name : 'Unknown',
                    'user_phone' => $user ? $user->phone : null,
                    'cart_items' => $userCarts->map(function ($cart) {
                        $cart->add_on_ids = json_decode($cart->add_on_ids, true);
                        $cart->add_on_qtys = json_decode($cart->add_on_qtys, true);
                        $cart->variation = json_decode($cart->variation, true);
                        $cart->item = Helpers::cart_product_data_formatting(
                            $cart->item,
                            $cart->variation,
                            $cart->add_on_ids,
                            $cart->add_on_qtys,
                            false,
                            app()->getLocale()
                        );
                        return $cart;
                    }),
                    'total_items' => $userCarts->count(),
                    'total_amount' => $userCarts->sum(function ($cart) {
                        return $cart->price * $cart->quantity;
                    }),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => $carts,
        ], 200);
    }

    /**
     * Get cart details for a specific customer
     */
    public function getCustomerCartDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $carts = Cart::where('user_id', $request->user_id)
            ->where('store_id', $store->id)
            ->where('is_guest', 0)
            ->get()
            ->map(function ($cart) {
                $cart->add_on_ids = json_decode($cart->add_on_ids, true);
                $cart->add_on_qtys = json_decode($cart->add_on_qtys, true);
                $cart->variation = json_decode($cart->variation, true);
                $cart->item = Helpers::cart_product_data_formatting(
                    $cart->item,
                    $cart->variation,
                    $cart->add_on_ids,
                    $cart->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $cart;
            });

        $user = User::find($request->user_id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->f_name . ' ' . $user->l_name,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'cart_items' => $carts,
            'total_items' => $carts->count(),
            'total_amount' => $carts->sum(function ($cart) {
                return $cart->price * $cart->quantity;
            }),
        ], 200);
    }

    /**
     * Add item to customer's cart
     */
    public function addItemToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        // Verify item belongs to vendor's store
        $item = Item::where('id', $request->item_id)
            ->where('store_id', $store->id)
            ->first();

        if (!$item) {
            return response()->json([
                'errors' => [
                    ['code' => 'item_not_found', 'message' => translate('messages.Item_not_found_or_does_not_belong_to_your_store')]
                ]
            ], 404);
        }

        // Check if item is active
        if ($item->status != 1) {
            return response()->json([
                'errors' => [
                    ['code' => 'item_unavailable', 'message' => translate('messages.Item_is_not_available')]
                ]
            ], 403);
        }

        // Check max cart quantity
        if ($item->maximum_cart_quantity && ($request->quantity > $item->maximum_cart_quantity)) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                ]
            ], 403);
        }

        // Check if user exists
        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'errors' => [
                    ['code' => 'user_not_found', 'message' => translate('messages.User_not_found')]
                ]
            ], 404);
        }

        // Check if item already in cart
        $existingCart = Cart::where('item_id', $request->item_id)
            ->where('item_type', 'App\Models\Item')
            ->where('user_id', $request->user_id)
            ->where('store_id', $store->id)
            ->where('is_guest', 0)
            ->first();

        if ($existingCart && json_decode($existingCart->variation, true) == $request->variation) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item', 'message' => translate('messages.Item_already_exists_in_customer_cart')]
                ]
            ], 403);
        }

        // Create cart item
        $cart = new Cart();
        $cart->user_id = $request->user_id;
        $cart->module_id = $item->module_id;
        $cart->store_id = $store->id;
        $cart->item_id = $request->item_id;
        $cart->is_guest = 0;
        $cart->add_on_ids = isset($request->add_on_ids) ? json_encode($request->add_on_ids) : json_encode([]);
        $cart->add_on_qtys = isset($request->add_on_qtys) ? json_encode($request->add_on_qtys) : json_encode([]);
        $cart->item_type = 'App\Models\Item';
        $cart->price = $request->price;
        $cart->quantity = $request->quantity;
        $cart->variation = isset($request->variation) ? json_encode($request->variation) : json_encode([]);
        $cart->save();

        $item->carts()->save($cart);

        // Send notification to customer
        $this->sendCartUpdateNotification($user, 'item_added', $item->name);

        return response()->json([
            'success' => true,
            'message' => translate('messages.Item_added_to_customer_cart_successfully'),
            'cart' => $cart,
        ], 200);
    }

    /**
     * Update item quantity in customer's cart
     */
    public function updateCartItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $cart = Cart::where('id', $request->cart_id)
            ->where('store_id', $store->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_not_found', 'message' => translate('messages.Cart_item_not_found')]
                ]
            ], 404);
        }

        $item = $cart->item_type === 'App\Models\Item' ? Item::find($cart->item_id) : null;

        if ($item && $item->maximum_cart_quantity && ($request->quantity > $item->maximum_cart_quantity)) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                ]
            ], 403);
        }

        $oldQuantity = $cart->quantity;
        $cart->quantity = $request->quantity;
        $cart->price = $request->price;
        if (isset($request->add_on_ids)) {
            $cart->add_on_ids = json_encode($request->add_on_ids);
        }
        if (isset($request->add_on_qtys)) {
            $cart->add_on_qtys = json_encode($request->add_on_qtys);
        }
        if (isset($request->variation)) {
            $cart->variation = json_encode($request->variation);
        }
        $cart->save();

        // Send notification to customer
        $user = User::find($cart->user_id);
        if ($user && $item) {
            $this->sendCartUpdateNotification($user, 'item_updated', $item->name);
        }

        return response()->json([
            'success' => true,
            'message' => translate('messages.Cart_item_updated_successfully'),
            'cart' => $cart,
        ], 200);
    }

    /**
     * Remove unavailable item from customer's cart with refund to wallet
     */
    public function removeUnavailableItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $cart = Cart::where('id', $request->cart_id)
            ->where('store_id', $store->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_not_found', 'message' => translate('messages.Cart_item_not_found')]
                ]
            ], 404);
        }

        $user = User::find($cart->user_id);
        if (!$user) {
            return response()->json([
                'errors' => [
                    ['code' => 'user_not_found', 'message' => translate('messages.User_not_found')]
                ]
            ], 404);
        }

        $refundAmount = $cart->price * $cart->quantity;
        $itemName = $cart->item ? (is_object($cart->item) ? $cart->item->name : 'Item') : 'Item';

        DB::beginTransaction();
        try {
            // Process refund to wallet
            $user->wallet_balance += $refundAmount;
            $user->save();

            // Create wallet transaction
            $walletTransaction = new WalletTransaction();
            $walletTransaction->user_id = $user->id;
            $walletTransaction->transaction_id = Str::uuid();
            $walletTransaction->transaction_type = 'cart_item_refund';
            $walletTransaction->credit = $refundAmount;
            $walletTransaction->balance = $user->wallet_balance;
            $walletTransaction->reference = 'Vendor removed unavailable item: ' . $itemName;
            $walletTransaction->created_at = now();
            $walletTransaction->updated_at = now();
            $walletTransaction->save();

            // Create modification request record
            CartModificationRequest::create([
                'cart_id' => $cart->id,
                'user_id' => $user->id,
                'store_id' => $store->id,
                'vendor_id' => $vendor->id,
                'original_item_id' => $cart->item_id,
                'request_type' => 'remove',
                'status' => 'approved', // Auto-approved for removal
                'reason' => $request->reason ?? 'Item unavailable',
                'refund_amount' => $refundAmount,
                'is_refunded' => true,
                'customer_responded_at' => now(),
            ]);

            // Delete the cart item
            $cart->delete();

            DB::commit();

            // Send notification to customer
            $this->sendCartUpdateNotification($user, 'item_removed_refunded', $itemName, $refundAmount);

            return response()->json([
                'success' => true,
                'message' => translate('messages.Item_removed_and_refund_processed'),
                'refund_amount' => $refundAmount,
                'new_wallet_balance' => $user->wallet_balance,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    ['code' => 'refund_failed', 'message' => translate('messages.Failed_to_process_refund')]
                ]
            ], 500);
        }
    }

    /**
     * Request item replacement (requires customer consent)
     */
    public function requestItemReplacement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|integer',
            'replacement_item_id' => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $cart = Cart::where('id', $request->cart_id)
            ->where('store_id', $store->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_not_found', 'message' => translate('messages.Cart_item_not_found')]
                ]
            ], 404);
        }

        // Verify replacement item belongs to vendor's store
        $replacementItem = Item::where('id', $request->replacement_item_id)
            ->where('store_id', $store->id)
            ->where('status', 1)
            ->first();

        if (!$replacementItem) {
            return response()->json([
                'errors' => [
                    ['code' => 'replacement_item_not_found', 'message' => translate('messages.Replacement_item_not_found_or_unavailable')]
                ]
            ], 404);
        }

        // Check if there's already a pending request for this cart item
        $existingRequest = CartModificationRequest::where('cart_id', $cart->id)
            ->where('request_type', 'replace')
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'errors' => [
                    ['code' => 'request_pending', 'message' => translate('messages.A_replacement_request_is_already_pending')]
                ]
            ], 403);
        }

        $user = User::find($cart->user_id);
        if (!$user) {
            return response()->json([
                'errors' => [
                    ['code' => 'user_not_found', 'message' => translate('messages.User_not_found')]
                ]
            ], 404);
        }

        // Create modification request
        $modificationRequest = CartModificationRequest::create([
            'cart_id' => $cart->id,
            'user_id' => $user->id,
            'store_id' => $store->id,
            'vendor_id' => $vendor->id,
            'original_item_id' => $cart->item_id,
            'replacement_item_id' => $request->replacement_item_id,
            'request_type' => 'replace',
            'status' => 'pending',
            'reason' => $request->reason ?? 'Item unavailable, replacement suggested',
            'refund_amount' => 0,
            'is_refunded' => false,
        ]);

        // Send notification to customer about replacement request
        $originalItem = Item::find($cart->item_id);
        $this->sendReplacementRequestNotification($user, $originalItem, $replacementItem, $modificationRequest);

        return response()->json([
            'success' => true,
            'message' => translate('messages.Replacement_request_sent_to_customer'),
            'request_id' => $modificationRequest->id,
            'original_item' => [
                'id' => $cart->item_id,
                'name' => $originalItem ? $originalItem->name : 'Unknown',
            ],
            'replacement_item' => [
                'id' => $replacementItem->id,
                'name' => $replacementItem->name,
                'price' => $replacementItem->price,
            ],
        ], 200);
    }

    /**
     * Get pending modification requests
     */
    public function getPendingRequests(Request $request)
    {
        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $requests = CartModificationRequest::with(['user', 'originalItem', 'replacementItem', 'cart'])
            ->where('store_id', $store->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ], 200);
    }

    /**
     * Get all modification requests history
     */
    public function getRequestsHistory(Request $request)
    {
        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 1;

        $requests = CartModificationRequest::with(['user', 'originalItem', 'replacementItem'])
            ->where('store_id', $store->id)
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        return response()->json([
            'success' => true,
            'total_size' => $requests->total(),
            'limit' => $limit,
            'offset' => $offset,
            'data' => $requests->items(),
        ], 200);
    }

    /**
     * Get alternative items for replacement suggestion
     */
    public function getAlternativeItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $vendor = $request->vendor;
        $store = $vendor->stores[0];

        $originalItem = Item::where('id', $request->item_id)
            ->where('store_id', $store->id)
            ->first();

        if (!$originalItem) {
            return response()->json([
                'errors' => [
                    ['code' => 'item_not_found', 'message' => translate('messages.Item_not_found')]
                ]
            ], 404);
        }

        // Get similar items from the same category that are available
        $alternatives = Item::where('store_id', $store->id)
            ->where('id', '!=', $originalItem->id)
            ->where('category_id', $originalItem->category_id)
            ->where('status', 1)
            ->where('is_approved', 1)
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'image' => $item->image_full_url,
                    'description' => $item->description,
                ];
            });

        return response()->json([
            'success' => true,
            'original_item' => [
                'id' => $originalItem->id,
                'name' => $originalItem->name,
                'price' => $originalItem->price,
                'category_id' => $originalItem->category_id,
            ],
            'alternatives' => $alternatives,
        ], 200);
    }

    /**
     * Send cart update notification to customer
     */
    private function sendCartUpdateNotification($user, $type, $itemName, $refundAmount = null)
    {
        $title = '';
        $description = '';

        switch ($type) {
            case 'item_added':
                $title = translate('messages.New_item_added_to_your_cart');
                $description = translate('messages.Vendor_added') . ' ' . $itemName . ' ' . translate('messages.to_your_cart');
                break;
            case 'item_updated':
                $title = translate('messages.Cart_item_updated');
                $description = translate('messages.Your_cart_item') . ' ' . $itemName . ' ' . translate('messages.has_been_updated_by_vendor');
                break;
            case 'item_removed_refunded':
                $title = translate('messages.Item_removed_from_cart');
                $description = $itemName . ' ' . translate('messages.was_removed_due_to_unavailability') . '. ' . translate('messages.Refund_of') . ' ' . Helpers::format_currency($refundAmount) . ' ' . translate('messages.credited_to_your_wallet');
                break;
        }

        $notificationData = [
            'title' => $title,
            'description' => $description,
            'order_id' => '',
            'image' => '',
            'type' => 'cart_update',
        ];

        if ($user->cm_firebase_token) {
            Helpers::send_push_notif_to_device($user->cm_firebase_token, $notificationData);
        }
    }

    /**
     * Send replacement request notification to customer
     */
    private function sendReplacementRequestNotification($user, $originalItem, $replacementItem, $request)
    {
        $notificationData = [
            'title' => translate('messages.Item_replacement_request'),
            'description' => translate('messages.Vendor_suggests_replacing') . ' ' . ($originalItem ? $originalItem->name : 'item') . ' ' . translate('messages.with') . ' ' . $replacementItem->name,
            'order_id' => '',
            'image' => '',
            'type' => 'cart_replacement_request',
            'request_id' => $request->id,
        ];

        if ($user->cm_firebase_token) {
            Helpers::send_push_notif_to_device($user->cm_firebase_token, $notificationData);
        }
    }
}
