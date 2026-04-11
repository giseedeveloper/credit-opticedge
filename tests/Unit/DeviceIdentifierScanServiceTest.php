<?php

use App\Services\DeviceIdentifierScanService;

it('extracts imei and serial candidates from barcode and text payloads', function () {
    $scan = app(DeviceIdentifierScanService::class)->parseClientPayload([
        'barcode_values' => [
            'IMEI: 356789012345678',
            'SN: TECNO-C30-0001',
        ],
        'raw_text' => 'Sticker IMEI 356789012345678 Serial Number TECNO-C30-0001',
        'detectors' => ['barcode', 'text'],
    ]);

    expect($scan['selected_imei'])->toBe('356789012345678')
        ->and($scan['selected_serial'])->toBe('TECNO-C30-0001')
        ->and($scan['imei_candidates'])->toContain('356789012345678')
        ->and($scan['serial_candidates'])->toContain('TECNO-C30-0001')
        ->and($scan['detectors'])->toBe(['barcode', 'text'])
        ->and($scan['confidence'])->toBeGreaterThan(0.8);
});

it('returns an empty scan payload when no identifier could be detected', function () {
    $scan = app(DeviceIdentifierScanService::class)->parseClientPayload([
        'raw_text' => 'Box photo with no readable identifiers',
    ]);

    expect($scan['selected_imei'])->toBeNull()
        ->and($scan['selected_serial'])->toBeNull()
        ->and($scan['imei_candidates'])->toBe([])
        ->and($scan['serial_candidates'])->toBe([])
        ->and($scan['confidence'])->toBe(0.0);
});
