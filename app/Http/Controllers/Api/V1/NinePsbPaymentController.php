<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;
use App\Services\NinePsbService;
use App\Notifications\WalletCredited;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class NinePsbPaymentController extends Controller
{
    private readonly string $baseUrl;
    private readonly string $publicKey;
    private readonly string $privateKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ninepsb.base_url', 'https://sandbox.v1.airpero.com/'), '/');
        $this->publicKey = config('services.ninepsb.public_key');
        $this->privateKey = config('services.ninepsb.private_key');
    }


    // -------------------------------------------------------------------------
    // Create Virtual Account
    // -------------------------------------------------------------------------
    public function createVirtualAccount(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedCustomer();

        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        if ($this->userAlreadyHasVirtualAccount($user)) {
            return $this->existingAccountResponse($user);
        }

        try {
            $account = $this->ninePsbService->createVirtualAccount($user);
            return $this->successResponse('Virtual account created successfully', $account, 201);
        } catch (\Exception $e) {
            Log::error('createVirtualAccount failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create virtual account', 500);
        }
    }

    // -------------------------------------------------------------------------
    // Initiate Transaction
    // -------------------------------------------------------------------------
    public function initiateTransaction(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedCustomer();
        if (!$user) {
            return $this->errorResponse('Unauthorized.. Kindly login', 401);
        }

        $request->validate([
            'amount' => 'required|numeric',
            'recipient_account' => 'required|string',
            'recipient_bank' => 'required|string',
            'narration' => 'nullable|string',
            'currency' => 'nullable|string',
        ]);

        try {
            $transaction = $this->ninePsbService->initiateTransaction($user, $request->all());
            return $this->successResponse('Transaction initiated successfully', $transaction, 201);
        } catch (\Exception $e) {
            Log::error('Transaction failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to initiate transaction: ' . $e->getMessage(), 500);
        }
    }
    // https://sandbox.v1.airpero.com/api/v1/merchants/customers
    // -------------------------------------------------------------------------
    // Webhook — handles both PAYIN_SUCCESS and WALLET_FUNDED
    // -------------------------------------------------------------------------
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        Log::info('9PSB Webhook received', ['payload' => $payload]);

        $event = $payload['event'] ?? null;

        match ($event) {
            'PAYIN_SUCCESS' => $this->handlePayinSuccess($payload),
            'WALLET_FUNDED' => $this->handleWalletFunded($payload),
            default => Log::warning('9PSB Webhook: unknown event', ['event' => $event]),
        };

        return response()->json(['status' => 'ok'], 200);
    }

    private function triggerVirtualAccountDebit(Order $order, $user): void
    {
        try {
            // Get virtual_user_id from the authenticated user
            $customerId = $user?->virtual_user_id;

            if (!$customerId) {
                Log::warning('Virtual account debit skipped — no virtual_user_id found', [
                    'order_id' => $order->id,
                    'user_id' => $user?->id,
                ]);
                return;
            }

            $amount = $order->order_amount;

            // If partially paid, only debit the wallet portion
            if ($order->payment_status === 'partially_paid') {
                $amount = $order->partially_paid_amount;
            }

            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders([
                    'x-public-key' => $this->publicKey,
                    'x-private-key' => $this->privateKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->retry(2, sleepMilliseconds: 500, throw: false)
                ->post("/api/v1/customers/{$customerId}/debit", [
                    'amount' => $amount,
                    'reason' => "Payment for Order #{$order->id}",
                ]);

            Log::info('9PSB debitVirtualAccountUser response', [
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'amount' => $amount,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('9PSB debit failed for order', [
                    'order_id' => $order->id,
                    'message' => $response->json('message') ?? 'Unknown error',
                    'errors' => $response->json('errors') ?? [],
                ]);
            }

        } catch (\Exception $e) {
            // Don't crash the order — just log the failure
            Log::error('9PSB debit exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Debit User Account by merchant
    // -------------------------------------------------------------------------
    public function debitVirtualAccountUser(Request $request, string $customerId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string',
        ]);

        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-public-key' => $this->publicKey,
                'x-private-key' => $this->privateKey,
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, sleepMilliseconds: 500, throw: false)
            ->post("/api/v1/customers/{$customerId}/debit", [
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
            ]);

        // Log everything
        Log::info('9PSB debitVirtualAccountUser raw response', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->json() ?? $response->body(),
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => false,
            'message' => $response->json('message') ?? 'Debit request failed',
            'errors' => $response->json('errors') ?? [],
        ], $response->status() ?: 500);
    }

    // -------------------------------------------------------------------------
    // PAYIN_SUCCESS — customer deposited into their virtual account
    // -------------------------------------------------------------------------
    private function handlePayinSuccess(array $payload): void
    {
        $data = $payload['data'];
        $transactionId = $data['transactionId'];
        $customerId = $data['customerId'];
        $reference = $data['reference'];
        $amount = (float) $data['amount'];
        $fee = (float) ($data['fee'] ?? 0);
        $senderName = $data['senderName'] ?? 'Someone';
        $senderAccount = $data['senderAccountNumber'] ?? null;
        $senderBank = $data['senderBankName'] ?? null;

        if (WalletTransaction::where('transaction_id', $transactionId)->exists()) {
            Log::info('9PSB PAYIN_SUCCESS: duplicate ignored', [
                'transactionId' => $transactionId,
                'reference' => $reference,
            ]);
            return;
        }

        $user = User::where('virtual_endUserId', $customerId)->first();

        if (!$user) {
            Log::error('9PSB PAYIN_SUCCESS: user not found', [
                'customerId' => $customerId,
            ]);
            return;
        }

        DB::transaction(function () use ($user, $amount, $transactionId, $reference) {
            $user->increment('wallet_balance', $amount);
            $newBalance = $user->fresh()->wallet_balance;

            WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'credit' => $amount,
                'debit' => 0,
                'admin_bonus' => 0,
                'balance' => $newBalance,
                'transaction_type' => 'add_fund',
                'reference' => $reference,
            ]);
        });

        Helpers::add_fund_push_notification($user->id);

        Log::info('9PSB PAYIN_SUCCESS: wallet credited', [
            'user_id' => $user->id,
            'amount' => $amount,
            'fee' => $fee,
            'reference' => $reference,
            'transactionId' => $transactionId,
            'sender' => $senderName,
            'sender_bank' => $senderBank,
            'sender_account' => $senderAccount,
        ]);
    }

    // -------------------------------------------------------------------------
    // WALLET_FUNDED — merchant's own wallet was topped up
    // -------------------------------------------------------------------------
    private function handleWalletFunded(array $payload): void
    {
        $data = $payload['data'];
        $transactionId = $data['transactionId'];
        $reference = $data['reference'];
        $amount = (float) $data['amount'];
        $senderName = $data['senderName'] ?? 'Someone';
        $senderAccount = $data['senderAccountNumber'] ?? null;
        $senderBank = $data['senderBankName'] ?? null;

        // 1. Prevent duplicate
        if (WalletTransaction::where('transaction_id', $transactionId)->exists()) {
            Log::info('9PSB WALLET_FUNDED: duplicate ignored', [
                'transactionId' => $transactionId,
                'reference' => $reference,
            ]);
            return;
        }

        // 2. Find merchant/admin — adjust to match your admin identification
        $merchant = User::where('is_admin', true)->first();

        if (!$merchant) {
            Log::error('9PSB WALLET_FUNDED: merchant user not found');
            return;
        }

        DB::transaction(function () use ($merchant, $amount, $transactionId, $reference) {
            // 3. Credit merchant wallet
            $merchant->increment('wallet_balance', $amount);

            // 4. Get fresh balance
            $newBalance = $merchant->fresh()->wallet_balance;

            // 5. Record transaction
            WalletTransaction::create([
                'user_id' => $merchant->id,
                'transaction_id' => $transactionId,
                'credit' => $amount,
                'debit' => 0,
                'admin_bonus' => 0,
                'balance' => $newBalance,
                'transaction_type' => 'add_fund_by_admin',
                'reference' => $reference,
            ]);
        });

        // 6. Notify merchant via FCM
        if ($merchant->device_token) {
            WalletCredited::sendPush(
                $merchant->device_token,
                $amount,
                $senderName,
            );
        }

        Log::info('9PSB WALLET_FUNDED: merchant wallet credited', [
            'merchant_id' => $merchant->id,
            'amount' => $amount,
            'reference' => $reference,
            'transactionId' => $transactionId,
            'sender' => $senderName,
            'sender_bank' => $senderBank,
            'sender_account' => $senderAccount,
        ]);
    }
    // -------------------------------------------------------------------------
    // Debit a customer's 9PSB virtual wallet
    // Moves funds FROM customer TO merchant wallet
    // -------------------------------------------------------------------------
    public function debitCustomer(User $user, float $amount, string $reason): bool
    {
        $customerId = $user->virtual_endUserId;

        if (!$customerId) {
            Log::warning('NinePsbWalletService::debitCustomer — no virtual_endUserId', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);
            return false;
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-public-key' => $this->publicKey,
                'x-private-key' => $this->privateKey,
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, sleepMilliseconds: 500, throw: false)
            ->post("/api/v1/customers/{$customerId}/debit", [
                'amount' => $amount,
                'reason' => $reason,
            ]);

        Log::info('NinePsbWalletService::debitCustomer response', [
            'user_id' => $user->id,
            'customer_id' => $customerId,
            'amount' => $amount,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        if (!$response->successful()) {
            Log::error('NinePsbWalletService::debitCustomer failed', [
                'user_id' => $user->id,
                'message' => $response->json('message') ?? 'Unknown error',
            ]);
            return false;
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Credit a customer's 9PSB virtual wallet
    // Moves funds FROM merchant wallet TO customer
    // -------------------------------------------------------------------------
    public function creditCustomer(User $user, float $amount, string $narration): bool
    {
        $customerId = $user->virtual_endUserId;

        if (!$customerId) {
            Log::warning('NinePsbWalletService::creditCustomer — no virtual_endUserId', [
                'user_id' => $user->id,
                'amount' => $amount,
                'narration' => $narration,
            ]);
            return false;
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-public-key' => $this->publicKey,
                'x-private-key' => $this->privateKey,
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, sleepMilliseconds: 500, throw: false)
            ->post("/api/v1/customers/{$customerId}/credit", [
                'amount' => $amount,
                'narration' => $narration,
            ]);

        Log::info('NinePsbWalletService::creditCustomer response', [
            'user_id' => $user->id,
            'customer_id' => $customerId,
            'amount' => $amount,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        if (!$response->successful()) {
            Log::error('NinePsbWalletService::creditCustomer failed', [
                'user_id' => $user->id,
                'message' => $response->json('message') ?? 'Unknown error',
            ]);
            return false;
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Deliveryman Payout — real-time bank transfer via 9PSB
    // POST /api/v1/merchants/payout - (9pSb endpoint)
    // -------------------------------------------------------------------------
    public function payoutDeliveryManToBank(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'accountNumber' => 'required|string|size:10',
            'bankCode' => 'required|string',
            'accountName' => 'required|string|max:255',
            'narration' => 'required|string|max:255',
        ]);

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders([
                    'x-public-key' => $this->publicKey,
                    'x-private-key' => $this->privateKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->retry(2, sleepMilliseconds: 500, throw: false)
                ->post('/api/v1/merchants/payout', [
                    'amount' => $validated['amount'],
                    'accountNumber' => $validated['accountNumber'],
                    'bankCode' => $validated['bankCode'],
                    'accountName' => $validated['accountName'],
                    'narration' => $validated['narration'],
                ]);

            Log::info('9PSB payoutToBank response', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'payload' => $validated,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payout initiated successfully',
                    'data' => $response->json(),
                ], $response->status());
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('message') ?? 'Payout request failed',
                'errors' => $response->json('errors') ?? [],
            ], $response->status() ?: 500);

        } catch (\Exception $e) {
            Log::error('9PSB payoutToBank exception', [
                'error' => $e->getMessage(),
                'payload' => $validated,
            ]);

            return $this->errorResponse('Payout failed: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // Get Banks List — all Nigerian banks and their codes
    // GET /api/v1/banks
    // -------------------------------------------------------------------------
    public function getBanks(): JsonResponse
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders([
                    'x-public-key' => $this->publicKey,
                    'x-private-key' => $this->privateKey,
                ])
                ->acceptJson()
                ->timeout(15)
                ->retry(2, sleepMilliseconds: 500, throw: false)
                ->get('/api/v1/banks');

            Log::info('9PSB getBanks response', [
                'status' => $response->status(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('data') ?? $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to fetch banks list',
            ], $response->status() ?: 500);

        } catch (\Exception $e) {
            Log::error('9PSB getBanks exception', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve banks: ' . $e->getMessage(), 500);
        }
    }

    public function nameEnquiry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountNumber' => 'required|string',
            'bankCode' => 'required|string',
        ]);

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders([
                    'x-public-key' => $this->publicKey,
                    'x-private-key' => $this->privateKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->retry(2, sleepMilliseconds: 500, throw: false)
                ->post('/api/v1/banks/enquiry', [
                    'accountNumber' => $validated['accountNumber'],
                    'bankCode' => $validated['bankCode'],
                ]);

            Log::info('9PSB nameEnquiry response', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'accountNumber' => $validated['accountNumber'],
                'bankCode' => $validated['bankCode'],
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $account_name = $body['data']['accountName'] ?? null;

                if (!$account_name) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account name not found in 9PSB response',
                        'raw' => $body,
                    ], 422);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Account name resolved successfully',
                    'data' => [
                        'accountName' => $account_name,
                        'accountNumber' => $validated['accountNumber'],
                        'bankCode' => $validated['bankCode'],
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('message') ?? 'Name enquiry failed',
                'errors' => $response->json('errors') ?? [],
            ], $response->status() ?: 500);

        } catch (\Exception $e) {
            Log::error('9PSB nameEnquiry exception', [
                'error' => $e->getMessage(),
                'accountNumber' => $validated['accountNumber'] ?? null,
                'bankCode' => $validated['bankCode'] ?? null,
            ]);

            return $this->errorResponse('Name enquiry failed: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // Record the wallet movement in WalletTransaction table
    // Call this AFTER a successful 9PSB API debit/credit to keep local DB in sync
    // -------------------------------------------------------------------------
    public function recordTransaction(
        User $user,
        float $credit,
        float $debit,
        string $transactionType,
        string $reference,
    ): void {
        DB::transaction(function () use ($user, $credit, $debit, $transactionType, $reference) {
            // Sync local wallet_balance
            $user->wallet_balance += ($credit - $debit);
            $user->save();

            WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_id' => Str::uuid(),
                'transaction_type' => $transactionType,
                'credit' => $credit,
                'debit' => $debit,
                'admin_bonus' => 0,
                'balance' => $user->wallet_balance,
                'reference' => $reference,
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    private function getAuthenticatedCustomer(): ?User
    {
        $user = auth()->user();
        return ($user instanceof User) ? $user : null;
    }

    private function userAlreadyHasVirtualAccount(User $user): bool
    {
        return !empty($user->virtual_account_number);
    }

    private function existingAccountResponse(User $user): JsonResponse
    {
        return $this->successResponse('User already has a virtual account', [
            'account_number' => $user->virtual_account_number,
            'account_name' => $user->virtual_account_name,
            'bank_name' => '9 Payment Service Bank',
        ]);
    }

    private function successResponse(string $message, mixed $data = [], int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message, 'data' => $data], $status);
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}