<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class CommunicationBridgeService
{
    /**
     * Dispatch WhatsApp Interactive Template for early stage reminders.
     */
    public function sendWhatsAppReminder(Customer $customer, string $message, array $quickReplies = []): bool
    {
        if (!$customer->phone) {
            return false;
        }

        // WhatsApp Business API Payload structure placeholder
        $payload = [
            'to' => $customer->phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [ 'text' => $message ],
                'action' => [
                    'buttons' => array_map(function ($reply, $index) {
                        return [
                            'type' => 'reply',
                            'reply' => ['id' => "qr_{$index}", 'title' => $reply]
                        ];
                    }, $quickReplies, array_keys($quickReplies))
                ]
            ]
        ];

        Log::info("WhatsApp API Triggered to {$customer->phone}: {$message}");
        
        // Log Conversation Thread logic internally
        return true;
    }

    /**
     * IVR (Interactive Voice Response) Hook
     * Used exclusively for Stage 2 & 3 Defaults to assert pressure visually.
     */
    public function triggerIVRCall(Customer $customer, string $scriptId): bool
    {
        if (!$customer->phone) {
            return false;
        }

        // External Hook to Twilio / Info-bip passing dynamic vars like [balance]
        Log::info("IVR Triggered for {$customer->phone}. Script ID: {$scriptId}");

        return true;
    }
}
