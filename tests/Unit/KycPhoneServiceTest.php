<?php

use App\Services\KycPhoneService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('normalizes a local tanzanian phone number to e164 metadata', function () {
    $phone = (new KycPhoneService)->normalizeForField(
        'phone',
        'phoneCountry',
        '0712 345 678',
        'TZ'
    );

    expect($phone)->not->toBeNull()
        ->and($phone['e164'])->toBe('+255712345678')
        ->and($phone['country_iso'])->toBe('TZ')
        ->and($phone['display'])->toBe('+255 712 345 678');
});

it('detects the country from an international number even if another country was selected', function () {
    $phone = (new KycPhoneService)->normalizeForField(
        'phone',
        'phoneCountry',
        '+254712345678',
        'TZ'
    );

    expect($phone)->not->toBeNull()
        ->and($phone['e164'])->toBe('+254712345678')
        ->and($phone['country_iso'])->toBe('KE');
});

it('throws a validation exception for invalid phone lengths', function () {
    expect(fn () => (new KycPhoneService)->normalizeForField(
        'phone',
        'phoneCountry',
        '12345',
        'TZ'
    ))->toThrow(ValidationException::class);
});
