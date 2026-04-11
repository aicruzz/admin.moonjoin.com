<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Cart;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\WalletTransaction;
use App\Http\Controllers\Controller;
use App\Models\CartModificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartModificationController extends Controller
{
    /**
     * Get pending modification requests for the customer
     */
    public function getPendingRequests(Request $request)
    {
        $user = $request->user();

        $requests = CartModificationRequest::with(['store', 'originalItem', 'replacementItem', 'cart'])
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'cart_id' => $req->cart_id,
                    'store' => [
                        'id' => $req->store->id ?? null,
                        'name' => $req->store->name ?? 'Unknown',
                        'logo' => $req->store->logo_full_url ?? null,
                    ],
                    'original_item' => $req->originalItem ? [
                        'id' => $req->originalItem->id,
                        'name' => $req->originalItem->name,
                        'price' => $req->originalItem->price,
                        'image' => $req->originalItem->image_full_url,
                    ] : null,
                    'replacement_item' => $req->replacementItem ? [
                        'id' => $req->replacementItem->id,
                        'name' => $req->replacementItem->name,
                        'price' => $req->replacementItem->price,
                        'image' => $req->replacementItem->image_full_url,
                    ] : null,
                    'request_type' => $req->request_type,
                    'reason' => $req->reason,
                    'created_at' => $req->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ], 200);
    }

    /**
     * Approve item replacement request
     */
    public function approveReplacement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();

        $modificationRequest = CartModificationRequest::where('id', $request->request_id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('request_type', 'replace')
            ->first();

        if (!$modificationRequest) {
            return response()->json([
                'errors' => [
                    ['code' => 'request_not_found', 'message' => translate('messages.Modification_request_not_found')]
                ]
            ], 404);
        }

        $cart = Cart::find($modificationRequest->cart_id);
        if (!$cart) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_not_found', 'message' => translate('messages.Cart_item_not_found')]
                ]
            ], 404);
        }

        $replacementItem = Item::where('id', $modificationRequest->replacement_item_id)
            ->where('status', 1)
            ->first();

        if (!$replacementItem) {
            return response()->json([
                'errors' => [
                    ['code' => 'replacement_unavailable', 'message' => translate('messages.Replacement_item_is_no_longer_available')]
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {
            $priceDifference = $replacementItem->price - $cart->price;

            // Handle price difference - if replacement is cheaper, refund difference
            if ($priceDifference < 0) {
                $refundAmount = abs($priceDifference) * $cart->quantity;
                $user->wallet_balance += $refundAmount;
                $user->save();

                // Create wallet transaction for the refund
                $walletTransaction = new WalletTransaction();
                $walletTransaction->user_id = $user->id;
                $walletTransaction->transaction_id = Str::uuid();
                $walletTransaction->transaction_type = 'cart_item_replacement_refund';
                $walletTransaction->credit = $refundAmount;
                $walletTransaction->balance = $user->wallet_balance;
                $walletTransaction->reference = 'Price difference refund for item replacement';
                $walletTransaction->created_at = now();
                $walletTransaction->updated_at = now();
                $walletTransaction->save();

                $modificationRequest->refund_amount = $refundAmount;
                $modificationRequest->is_refunded = true;
            }

            // Update cart with replacement item
            $cart->item_id = $replacementItem->id;
            $cart->price = $replacementItem->price;
            $cart->variation = json_encode([]);
            $cart->add_on_ids = json_encode([]);
            $cart->add_on_qtys = json_encode([]);
            $cart->save();

            // Update the polymorphic relationship
            $replacementItem->carts()->save($cart);

            // Update modification request status
            $modificationRequest->status = 'approved';
            $modificationRequest->customer_responded_at = now();
            $modificationRequest->save();

            DB::commit();

            // Send notification to vendor
            $this->sendVendorNotification($modificationRequest, 'approved');

            return response()->json([
                'success' => true,
                'message' => translate('messages.Item_replacement_approved_successfully'),
                'price_difference' => $priceDifference,
                'refund_amount' => $priceDifference < 0 ? abs($priceDifference) * $cart->quantity : 0,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    ['code' => 'replacement_failed', 'message' => translate('messages.Failed_to_process_replacement')]
                ]
            ], 500);
        }
    }

    /**
     * Reject item replacement request
     */
    public function rejectReplacement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer',
            'action' => 'required|in:reject,remove_with_refund',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();

        $modificationRequest = CartModificationRequest::where('id', $request->request_id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$modificationRequest) {
            return response()->json([
                'errors' => [
                    ['code' => 'request_not_found', 'message' => translate('messages.Modification_request_not_found')]
                ]
            ], 404);
        }

        $cart = Cart::find($modificationRequest->cart_id);

        DB::beginTransaction();
        try {
            if ($request->action === 'remove_with_refund' && $cart) {
                // Customer wants to remove item and get refund
                $refundAmount = $cart->price * $cart->quantity;
                $user->wallet_balance += $refundAmount;
                $user->save();

                // Create wallet transaction
                $walletTransaction = new WalletTransaction();
                $walletTransaction->user_id = $user->id;
                $walletTransaction->transaction_id = Str::uuid();
                $walletTransaction->transaction_type = 'cart_item_refund';
                $walletTransaction->credit = $refundAmount;
                $walletTransaction->balance = $user->wallet_balance;
                $walletTransaction->reference = 'Refund for removed unavailable item';
                $walletTransaction->created_at = now();
                $walletTransaction->updated_at = now();
                $walletTransaction->save();

                $modificationRequest->refund_amount = $refundAmount;
                $modificationRequest->is_refunded = true;

                // Delete cart item
                $cart->delete();
            }

            // Update modification request status
            $modificationRequest->status = 'rejected';
            $modificationRequest->customer_responded_at = now();
            $modificationRequest->save();

            DB::commit();

            // Send notification to vendor
            $this->sendVendorNotification($modificationRequest, 'rejected');

            return response()->json([
                'success' => true,
                'message' => $request->action === 'remove_with_refund' 
                    ? translate('messages.Item_removed_and_refunded') 
                    : translate('messages.Replacement_rejected'),
                'refund_amount' => $request->action === 'remove_with_refund' && $cart ? $cart->price * $cart->quantity : 0,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    ['code' => 'action_failed', 'message' => translate('messages.Failed_to_process_action')]
                ]
            ], 500);
        }
    }

    /**
     * Get modification requests history for customer
     */
    public function getRequestsHistory(Request $request)
    {
        $user = $request->user();

        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 1;

        $requests = CartModificationRequest::with(['store', 'originalItem', 'replacementItem'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        $data = $requests->getCollection()->map(function ($req) {
            return [
                'id' => $req->id,
                'store' => [
                    'id' => $req->store->id ?? null,
                    'name' => $req->store->name ?? 'Unknown',
                ],
                'original_item' => $req->originalItem ? [
                    'id' => $req->originalItem->id,
                    'name' => $req->originalItem->name,
                ] : null,
                'replacement_item' => $req->replacementItem ? [
                    'id' => $req->replacementItem->id,
                    'name' => $req->replacementItem->name,
                ] : null,
                'request_type' => $req->request_type,
                'status' => $req->status,
                'reason' => $req->reason,
                'refund_amount' => $req->refund_amount,
                'is_refunded' => $req->is_refunded,
                'created_at' => $req->created_at,
                'responded_at' => $req->customer_responded_at,
            ];
        });

        return response()->json([
            'success' => true,
            'total_size' => $requests->total(),
            'limit' => $limit,
            'offset' => $offset,
            'data' => $data,
        ], 200);
    }

    /**
     * Send notification to vendor about customer response
     */
    private function sendVendorNotification($modificationRequest, $status)
    {
        $vendor = \App\Models\Vendor::find($modificationRequest->vendor_id);
        if (!$vendor) return;

        $originalItem = Item::find($modificationRequest->original_item_id);
        $user = User::find($modificationRequest->user_id);

        $title = $status === 'approved' 
            ? translate('messages.Replacement_approved') 
            : translate('messages.Replacement_rejected');

        $description = translate('messages.Customer') . ' ' . ($user ? $user->f_name : '') . ' ' 
            . ($status === 'approved' 
                ? translate('messages.approved_the_replacement_for') 
                : translate('messages.rejected_the_replacement_for')) 
            . ' ' . ($originalItem ? $originalItem->name : 'item');

        $notificationData = [
            'title' => $title,
            'description' => $description,
            'order_id' => '',
            'image' => '',
            'type' => 'cart_replacement_response',
        ];

        if ($vendor->firebase_token) {
            Helpers::send_push_notif_to_device($vendor->firebase_token, $notificationData);
        }
    }
}
