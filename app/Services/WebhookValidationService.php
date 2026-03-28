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
    public function validateUniqueReference(string $referenceId, string $method): bool
    {
        return !Transaction::where('reference', $referenceId)
            ->where('method', $method)
            ->exists();
    }

    /**
     * Verify the webhook signature to ensure it actually came from the MNO.
     * Hardcoded for demonstration, but typically uses hash_hmac.
     */
    public function verifySignature(Request $request, string $providerSecret): bool
    {
        // Example logic
        $signature = $request->header('X-MNO-Signature');
        
        if (!$signature) {
            return false;
        }

        // $payload = $request->getContent();
        // $expected = base64_encode(hash_hmac('sha256', $payload, $providerSecret, true));
        // return hash_equals($expected, $signature);
        
        return true; // Placeholder
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
