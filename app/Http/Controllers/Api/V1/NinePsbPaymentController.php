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

    // -------------------------------------------------------------------------
    // Trigger Virtual Account Debit (internal use)
    // -------------------------------------------------------------------------
    private function triggerVirtualAccountDebit($order, $user): void
    {
        try {
            $customerId = $user?->virtual_user_id;

            if (!$customerId) {
                Log::warning('Virtual account debit skipped — no virtual_user_id found', [
                    'order_id' => $order->id,
                    'user_id' => $user?->id,
                ]);
                return;
            }

            $amount = $order->order_amount;

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

        if (WalletTransaction::where('transaction_id', $transactionId)->exists()) {
            Log::info('9PSB WALLET_FUNDED: duplicate ignored', [
                'transactionId' => $transactionId,
                'reference' => $reference,
            ]);
            return;
        }

        $merchant = User::where('is_admin', true)->first();

        if (!$merchant) {
            Log::error('9PSB WALLET_FUNDED: merchant user not found');
            return;
        }

        DB::transaction(function () use ($merchant, $amount, $transactionId, $reference) {
            $merchant->increment('wallet_balance', $amount);
            $newBalance = $merchant->fresh()->wallet_balance;

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
    // Vendor/Store Payout — via HTTP route (keeps original for API use)
    // -------------------------------------------------------------------------
    public function payoutStoreToBank(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'accountNumber' => 'required|string|size:10',
            'bankCode' => 'required|string',
            'accountName' => 'required|string|max:255',
            'narration' => 'required|string|max:255',
        ]);

        $result = $this->payoutStoreToBankDirect(
            amount: (float) $validated['amount'],
            accountNumber: $validated['accountNumber'],
            bankCode: $validated['bankCode'],
            accountName: $validated['accountName'],
            narration: $validated['narration'],
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    // -------------------------------------------------------------------------
    // Vendor/Store Payout — called internally without a Request object
    // FIX: This avoids ValidationException when called manually from w_request
    // -------------------------------------------------------------------------
    public function payoutStoreToBankDirect(
        float $amount,
        string $accountNumber,
        string $bankCode,
        string $accountName,
        string $narration
    ): array {
        Log::info('9PSB payoutStoreToBankDirect | CALLED', [
            'amount' => $amount,
            'accountNumber' => $accountNumber,
            'bankCode' => $bankCode,
            'accountName' => $accountName,
            'narration' => $narration,
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
                    'amount' => $amount,
                    'accountNumber' => $accountNumber,
                    'bankCode' => $bankCode,
                    'accountName' => $accountName,
                    'narration' => $narration,
                ]);

            Log::info('9PSB payoutStoreToBankDirect | RESPONSE', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Payout initiated successfully',
                    'data' => $response->json(),
                ];
            }

            Log::error('9PSB payoutStoreToBankDirect | FAILED', [
                'status' => $response->status(),
                'message' => $response->json('message') ?? 'Payout request failed',
                'errors' => $response->json('errors') ?? [],
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Payout request failed',
                'errors' => $response->json('errors') ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('9PSB payoutStoreToBankDirect | EXCEPTION', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'success' => false,
                'message' => 'Payout failed: ' . $e->getMessage(),
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Deliveryman Payout — via HTTP route
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

            Log::info('9PSB payoutDeliveryManToBank response', [
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
            Log::error('9PSB payoutDeliveryManToBank exception', [
                'error' => $e->getMessage(),
                'payload' => $validated,
            ]);

            return $this->errorResponse('Payout failed: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // Get Banks List
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
                'body' => $response->json() ?? $response->body(),
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

    // -------------------------------------------------------------------------
    // Name Enquiry — via HTTP route (keeps original for API use)
    // -------------------------------------------------------------------------
    public function nameEnquiry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountNumber' => 'required|string',
            'bankCode' => 'required|string',
        ]);

        $inputMethod = strtolower(trim($validated['bankCode']));

        $bankCodeMap = [
            'opay'                   => '100004',
            'moniepoint'             => '090405',
            'palmpay'                => '100033',
            'smartcash psb'          => '120001',
            'access bank plc'        => '000014',
            'united bank for africa' => '000004',
            'kuda microfinance bank' => '090267',
            'airpero'                => '090133',
        ];

        $finalBankCode = $bankCodeMap[$inputMethod] ?? $validated['bankCode'];

        $result = $this->nameEnquiryDirect(
            accountNumber: $validated['accountNumber'],
            bankCode: $finalBankCode,
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Account name resolved successfully',
                'data' => [
                    'accountName'   => $result['accountName'],
                    'accountNumber' => $validated['accountNumber'],
                    'bankCode'      => $finalBankCode,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Name enquiry failed',
        ], 422);
    }


    // -------------------------------------------------------------------------
    // Name Enquiry — called internally without a Request object
    // FIX: This avoids ValidationException when called manually from w_request
    // -------------------------------------------------------------------------
    public function nameEnquiryDirect(string $accountNumber, string $bankCode): array
    {
        Log::info('9PSB nameEnquiryDirect | CALLED', [
            'accountNumber' => $accountNumber,
            'bankCode' => $bankCode,
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
                    'accountNumber' => $accountNumber,
                    'bankCode' => $bankCode,
                ]);

            Log::info('9PSB nameEnquiryDirect | RESPONSE', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'accountNumber' => $accountNumber,
                'bankCode' => $bankCode,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $account_name = $body['data']['customer']['account']['name']
                    ?? $body['data']['accountName']
                    ?? $body['data']['account_name']
                    ?? null;

                if (!$account_name) {
                    Log::error('9PSB nameEnquiryDirect | ACCOUNT NAME MISSING', [
                        'raw' => $body,
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Account name not found in 9PSB response',
                        'raw' => $body,
                    ];
                }

                return [
                    'success' => true,
                    'accountName' => $account_name,
                ];
            }

            Log::error('9PSB nameEnquiryDirect | HTTP FAILED', [
                'status' => $response->status(),
                'message' => $response->json('message') ?? 'Name enquiry failed',
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Name enquiry failed',
            ];

        } catch (\Exception $e) {
            Log::error('9PSB nameEnquiryDirect | EXCEPTION', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'accountNumber' => $accountNumber,
                'bankCode' => $bankCode,
            ]);

            return [
                'success' => false,
                'message' => 'Name enquiry failed: ' . $e->getMessage(),
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Record wallet movement in WalletTransaction table
    // -------------------------------------------------------------------------
    public function recordTransaction(
        User $user,
        float $credit,
        float $debit,
        string $transactionType,
        string $reference,
    ): void {
        DB::transaction(function () use ($user, $credit, $debit, $transactionType, $reference) {
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