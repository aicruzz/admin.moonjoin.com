<?php

namespace App\Services\Disbursement;

use App\Http\Controllers\Api\V1\NinePsbPaymentController;
use Illuminate\Support\Facades\Log;

class DisbursementPayoutService
{
    private const BANK_CODE_MAP = [
        'opay' => '100004',
        'moniepoint' => '090405',
        'palmpay' => '100033',
        'smartcash psb' => '120004',
        'access bank plc' => '000014',
        'united bank for africa' => '000004',
        'kuda microfinance bank' => '090267',
        'airpero' => '120001',
    ];

    public function __construct(private readonly NinePsbPaymentController $ninePsb)
    {
    }

    /**
     * Attempt a 9PSB payout for one disbursement detail.
     *
     * @param  float  $amount         Amount to send, in NGN.
     * @param  string $methodName     The withdrawal method's method_name (e.g. "Opay", "Bank Transfer").
     * @param  array  $methodFields   Decoded method_fields from DisbursementWithdrawalMethod.
     * @param  string $narration      Payout narration shown on the recipient's statement.
     *
     * @return array{success: bool, message: string, account_name?: string, bank_name?: string, data?: mixed}
     */
    public function payout(float $amount, ?string $methodName, array $methodFields, string $narration): array
    {
        $accountNumber = $methodFields['account_number'] ?? null;
        $bankCodeFromForm = $methodFields['bank_code'] ?? null;
        $methodKey = strtolower(trim((string) $methodName));

        if (!$accountNumber) {
            return ['success' => false, 'message' => 'Recipient has no account_number on disbursement method'];
        }

        $bankCode = self::BANK_CODE_MAP[$methodKey] ?? $bankCodeFromForm;

        try {
            $banksRaw = json_decode($this->ninePsb->getBanks()->getContent(), true);

            $bankList = $banksRaw['data']['data']['bankList']
                ?? $banksRaw['data']['bankList']
                ?? $banksRaw['data']['data']['data']['bankList']
                ?? [];

            $matchedBank = null;
            if ($bankCode) {
                $matchedBank = collect($bankList)->firstWhere('bankCode', $bankCode);
            }

            if (!$matchedBank && $methodKey) {
                $matchedBank = collect($bankList)->first(function ($b) use ($methodKey) {
                    $apiName = strtolower(trim($b['bankName'] ?? ''));
                    if ($apiName === '' || $methodKey === '') {
                        return false;
                    }
                    if (str_contains($apiName, $methodKey) || str_contains($methodKey, $apiName)) {
                        return true;
                    }
                    if ($methodKey === 'united bank for africa' && in_array($apiName, ['uba', 'united bank for africa'], true)) {
                        return true;
                    }
                    if (str_contains($methodKey, 'smartcash') && str_contains($apiName, 'smartcash')) {
                        return true;
                    }
                    return false;
                });

                if ($matchedBank) {
                    $bankCode = $matchedBank['bankCode'] ?? $bankCode;
                }
            }

            if (!$matchedBank || !$bankCode) {
                return ['success' => false, 'message' => 'Bank code could not be resolved for method "' . $methodName . '"'];
            }

            $bankName = $matchedBank['bankName'] ?? $methodName;

            $enquiry = $this->ninePsb->nameEnquiryDirect($accountNumber, $bankCode);
            if (!($enquiry['success'] ?? false)) {
                return ['success' => false, 'message' => $enquiry['message'] ?? 'Name enquiry failed'];
            }

            $accountName = $enquiry['accountName'];

            $payout = $this->ninePsb->payoutStoreToBankDirect(
                amount: $amount,
                accountNumber: $accountNumber,
                bankCode: $bankCode,
                accountName: $accountName,
                narration: $narration,
            );

            if (!($payout['success'] ?? false)) {
                return ['success' => false, 'message' => $payout['message'] ?? '9PSB payout rejected'];
            }

            return [
                'success' => true,
                'message' => 'Payout sent',
                'account_name' => $accountName,
                'bank_name' => $bankName,
                'data' => $payout['data'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('DisbursementPayoutService exception', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'method' => $methodName,
            ]);
            return ['success' => false, 'message' => '9PSB exception: ' . $e->getMessage()];
        }
    }
}