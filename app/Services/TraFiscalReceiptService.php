<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TRA VFD / EFD fiscal receipt integration — structured payload + optional verify hook.
 */
class TraFiscalReceiptService
{
    public function __construct(
        private AccountingService $accounting,
    ) {}

    /**
     * @return array{
     *     status: string,
     *     payload: array<string, mixed>,
     *     verification_url: ?string,
     *     tra_response: ?array<string, mixed>
     * }
     */
    public function issueForTransaction(Transaction $transaction): array
    {
        $payload = $this->accounting->buildReceiptPayload($transaction);
        $payload = array_merge($payload, [
            'issuer' => config('app.name'),
            'currency' => 'TZS',
            'fiscal_status' => 'pending_tra_submission',
            'issued_at' => now()->toIso8601String(),
        ]);

        $verifyBase = rtrim((string) config('services.tra.vfd_verify_base_url', ''), '/');
        $verificationUrl = $verifyBase !== ''
            ? $verifyBase.'?'.http_build_query([
                'ref' => $payload['receipt_number'],
                'amount' => $payload['amount'],
            ])
            : null;

        $traResponse = null;
        $status = 'payload_ready';

        if (filter_var(env('TRA_EFD_ENABLED', false), FILTER_VALIDATE_BOOL) && $verificationUrl) {
            try {
                $response = Http::timeout(12)->get($verificationUrl);
                if ($response->successful()) {
                    $traResponse = $response->json();
                    $status = 'verified_stub';
                }
            } catch (\Throwable $exception) {
                Log::warning('tra.efd.verify_failed', [
                    'transaction_id' => $transaction->id,
                    'message' => $exception->getMessage(),
                ]);
                $status = 'verify_unreachable';
            }
        }

        Log::channel('audit_trail')->info('tra.fiscal_receipt.prepared', [
            'transaction_id' => $transaction->id,
            'reference' => $payload['receipt_number'],
            'status' => $status,
        ]);

        return [
            'status' => $status,
            'payload' => $payload,
            'verification_url' => $verificationUrl,
            'tra_response' => $traResponse,
        ];
    }
}
