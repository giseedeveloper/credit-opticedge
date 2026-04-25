<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    /**
     * Scan for fraud heuristics before approving loan
     */
    public function checkApplicationIntegrity(Customer $customer): array
    {
        $metrics = [
            'risk_level' => 'low',
            'flags' => [],
        ];

        // 1. Velocity Checks: Ensure vendor hasn't sold suspiciously quickly
        $vendorId = $customer->dealer_id;
        if ($vendorId) {
            $salesLastHour = Loan::where('dealer_id', $vendorId)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($salesLastHour > 10) {
                $metrics['flags'][] = 'velocity_check_trigger';
                $metrics['risk_level'] = 'high';
            }
        }

        // 2. Metadata / Biometric Hash overlaps (Dummy check logic for deduplication)
        // E.g. Using the exact same identical phone metadata
        $metadata = $customer->metadata ?? [];
        if (isset($metadata['device_fingerprint'])) {
            $duplicateDevice = Customer::where('metadata->device_fingerprint', $metadata['device_fingerprint'])
                ->where('id', '!=', $customer->id)
                ->count();

            if ($duplicateDevice > 1) {
                $metrics['flags'][] = 'device_fingerprint_duplicated';
                $metrics['risk_level'] = 'critical';
            }
        }

        if ($metrics['risk_level'] !== 'low') {
            Log::warning('Fraud Engine: Suspicious boarding intercepted.', $metrics);
        }

        return $metrics;
    }
}
