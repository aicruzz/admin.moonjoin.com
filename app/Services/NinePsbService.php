<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NinePsbService
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

    /**
     * Shared HTTP client with auth headers
     */
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-public-key' => $this->publicKey,
                'x-private-key' => $this->privateKey,
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, sleepMilliseconds: 500, throw: false);
    }

    /**
     * Create a dedicated virtual account for a user.
     *
     * @throws \Exception
     */
    public function createVirtualAccount(User $user): array
    {
        $response = $this->http()->post('/api/v1/customers', [
            'fullName' => trim("{$user->f_name} {$user->l_name}"),
            'email' => $user->email,
            'phoneNumber' => $user->phone,
        ]);

        Log::info('9PSB createVirtualAccount raw response', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->json() ?? $response->body(),
        ]);

        if ($response->failed()) {
            $message = $response->json('message') ?? 'Failed to create virtual account';

            Log::error('NinePsbService::createVirtualAccount failed', [
                'status' => $response->status(),
                'message' => $message,
                'user_id' => $user->id,
            ]);

            throw new \Exception($message, $response->status());
        }

        $data = $response->json('data') ?? $response->json();
        $accountNumber = $data['accountNumber'] ?? null;
        $accountName = $data['fullName'] ?? null;
        $bankName = $data['bankName'] ?? '9 Payment Service Bank';
        $endUserId = $data['id'] ?? null;

        $user->update([
            'virtual_account_number' => $accountNumber,
            'virtual_account_name' => $accountName,
            'virtual_bank_name' => $bankName,
            'virtual_endUserId' => $endUserId,
        ]);

        return [
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'bank_name' => $bankName,
            'end_user_id' => $endUserId,
        ];
    }

    /**
     * Debit a customer's virtual account after a paid order.
     * Called automatically after order placement.
     */
    public function debitCustomer(Order $order, User $user): void
    {
        try {

            $customerId = $user?->virtual_endUserId;

            if (!$customerId) {
                Log::warning('9PSB debit skipped — no virtual_endUserId found', [
                    'order_id' => $order->id,
                    'user_id' => $user?->id,
                ]);
                return;
            }

            $amount = $order->payment_status === 'partially_paid'
                ? $order->partially_paid_amount
                : $order->order_amount;

            $response = $this->http()->post("/api/v1/customers/{$customerId}/debit", [
                'amount' => $amount,
                'reason' => "Payment for Order #{$order->id}",
            ]);

            Log::info('9PSB debit response', [
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'amount' => $amount,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('9PSB debit failed', [
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

    /**
     * Initiate a transaction.
     * NOTE: Confirm the correct endpoint and auth with 9PSB docs.
     */
    public function initiateTransaction(User $user, array $data): array
    {
        $payload = [
            'endUserId' => $data['endUserId'],
            'amount' => $data['amount'],
            'recipient_account' => $data['recipient_account'],
            'recipient_bank' => $data['recipient_bank'],
            'narration' => $data['narration'] ?? 'Payment From MoonJoin Technologies',
            'currency' => $data['currency'] ?? 'NGN',
        ];


        $response = $this->http()->post('/api/transaction/initiate', $payload);

        if ($response->failed()) {
            $message = $response->json('message') ?? 'Failed to initiate transaction';

            Log::error('NinePsbService::initiateTransaction failed', [
                'status' => $response->status(),
                'message' => $message,
                'user_id' => $user->id,
                'end_user_id' => $data['endUserId'],
            ]);

            throw new \Exception($message, $response->status());
        }

        $transaction = $response->json();

        Log::info('9PSB transaction initiated', [
            'user_id' => $user->id,
            'response' => $transaction,
        ]);

        return $transaction;
    }

    /**
     * Verify a transaction by its reference.
     */
    public function verifyTransaction(string $transactionReference): bool
    {

        $response = $this->http()->get("/api/transactions/{$transactionReference}/verify");

        if ($response->failed()) {
            Log::error('NinePsbService::verifyTransaction failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'reference' => $transactionReference,
            ]);

            return false;
        }

        return $response->json('status') === 'success';
    }
}