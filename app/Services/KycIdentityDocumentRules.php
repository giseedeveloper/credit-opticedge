<?php

namespace App\Services;

use Illuminate\Validation\Rule;

class KycIdentityDocumentRules
{
    /**
     * @return array<string, mixed>
     */
    public function documentNumberRules(?string $idType, ?string $customerId = null): array
    {
        $unique = Rule::unique('customers', 'nida_number');

        if ($customerId) {
            $unique = $unique->ignore($customerId);
        }

        $base = ['required', 'string', $unique];

        return match ($idType) {
            'nida' => array_merge($base, ['digits:20']),
            'voter_card' => array_merge($base, ['regex:/^[A-Z0-9][A-Z0-9\-\/]{7,23}$/i']),
            'passport' => array_merge($base, ['regex:/^[A-Z][A-Z0-9]{5,11}$/i']),
            'driving_license' => array_merge($base, ['regex:/^[A-Z0-9][A-Z0-9\-\/]{4,19}$/i']),
            default => array_merge($base, ['min:5', 'max:24']),
        };
    }

    public function documentNumberLabel(?string $idType): string
    {
        return match ($idType) {
            'nida' => 'NIDA number',
            'voter_card' => 'Voter card number',
            'passport' => 'Passport number',
            'driving_license' => 'Driving licence number',
            default => 'ID number',
        };
    }

    public function documentNumberHint(?string $idType): string
    {
        return match ($idType) {
            'nida' => '20 digits exactly as printed on NIDA.',
            'voter_card' => 'Use the voter ID number from the card (letters, numbers, dashes).',
            'passport' => 'Passport number, usually 6–12 characters.',
            'driving_license' => 'Licence number as printed on the card.',
            default => 'Enter the ID number exactly as shown on the document.',
        };
    }

    public function documentNumberMaxLength(?string $idType): int
    {
        return match ($idType) {
            'nida' => 20,
            'voter_card' => 24,
            'passport' => 12,
            'driving_license' => 20,
            default => 24,
        };
    }

    /**
     * @return array<int, string>
     */
    public function supportedIdTypes(): array
    {
        return ['nida', 'voter_card', 'passport', 'driving_license'];
    }
}
