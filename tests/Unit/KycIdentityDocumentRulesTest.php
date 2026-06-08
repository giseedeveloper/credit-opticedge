<?php

use App\Services\KycIdentityDocumentRules;
use Tests\TestCase;

uses(TestCase::class);

it('requires exactly 20 digits for nida documents', function () {
    $rules = app(KycIdentityDocumentRules::class)->documentNumberRules('nida');

    expect($rules)->toContain('digits:20');
});

it('uses voter card regex for voter_card documents', function () {
    $rules = app(KycIdentityDocumentRules::class)->documentNumberRules('voter_card');

    expect(collect($rules)->first(fn ($rule) => is_string($rule) && str_contains($rule, 'regex:')))->toContain('A-Z0-9');
});

it('exposes labels hints and max lengths per id type', function () {
    $service = app(KycIdentityDocumentRules::class);

    expect($service->documentNumberLabel('passport'))->toBe('Passport number')
        ->and($service->documentNumberHint('nida'))->toContain('20 digits')
        ->and($service->documentNumberMaxLength('nida'))->toBe(20)
        ->and($service->supportedIdTypes())->toContain('voter_card');
});
