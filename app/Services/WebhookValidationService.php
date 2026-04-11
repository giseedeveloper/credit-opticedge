<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookValidationService
{
    /**
     * Verify if a transaction reference has already been processed.
     */
    public function validateUniqueReference(string $referenceId): bool
    {
        return ! Transaction::query()
            ->where('reference', $referenceId)
            ->orWhere('external_reference', $referenceId)
            ->exists();
    }

    /**
     * Verify the webhook signature to ensure it actually came from the MNO.
     */
    public function verifySignature(Request $request, ?string $providerSecret): bool
    {
        if (! $providerSecret) {
            return app()->environment(['local', 'testing']);
        }

        $signatureHeader = config('services.collections.signature_header', 'X-MNO-Signature');
        $signature = $request->header($signatureHeader);

        if (! is_string($signature) || trim($signature) === '') {
            return false;
        }

        $payload = $request->getContent();
        $expectedBase64 = base64_encode(hash_hmac('sha256', $payload, $providerSecret, true));
        $expectedHex = hash_hmac('sha256', $payload, $providerSecret);

        return hash_equals($expectedBase64, trim($signature))
            || hash_equals($expectedHex, trim($signature));
    }

    /**
     * Push to a manual reconciliation queue if mapping fails.
     */
    public function queueForManualReconciliation(array $payload, string $reason): void
    {
        Log::error("Manual Reconciliation Required: {$reason}", $payload);
        // Dispatch to DB table 'reconciliations' or queue
    }
}
