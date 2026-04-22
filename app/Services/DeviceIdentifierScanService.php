<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DeviceIdentifierScanService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     selected_imei: ?string,
     *     selected_imei_2: ?string,
     *     selected_serial: ?string,
     *     imei_candidates: array<int, string>,
     *     serial_candidates: array<int, string>,
     *     barcode_values: array<int, string>,
     *     raw_text: string,
     *     detectors: array<int, string>,
     *     confidence: float
     * }
     */
    public function parseClientPayload(array $payload): array
    {
        $barcodeValues = collect($payload['barcode_values'] ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => $this->normalizeWhitespace($value))
            ->values();

        $rawText = $this->normalizeWhitespace((string) ($payload['raw_text'] ?? ''));

        $combinedText = trim(collect([$rawText, ...$barcodeValues->all()])
            ->filter()
            ->implode("\n"));

        $imeiCandidates = collect([$combinedText, ...$barcodeValues->all()])
            ->flatMap(fn (string $text): array => $this->extractImeis($text))
            ->unique()
            ->values();

        $serialCandidates = collect([$combinedText, ...$barcodeValues->all()])
            ->flatMap(fn (string $text): array => $this->extractSerials($text))
            ->reject(fn (string $serial): bool => $imeiCandidates->contains($serial))
            ->unique()
            ->values();

        $selectedImei = $this->pickBestImei($barcodeValues, $combinedText, $imeiCandidates);
        $selectedImei2 = $this->pickSecondImei($selectedImei, $imeiCandidates);
        $selectedSerial = $this->pickBestSerial($barcodeValues, $combinedText, $serialCandidates);

        return [
            'selected_imei' => $selectedImei,
            'selected_imei_2' => $selectedImei2,
            'selected_serial' => $selectedSerial,
            'imei_candidates' => $imeiCandidates->all(),
            'serial_candidates' => $serialCandidates->all(),
            'barcode_values' => $barcodeValues->all(),
            'raw_text' => Str::limit($combinedText, 4000, ''),
            'detectors' => collect($payload['detectors'] ?? [])
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->values()
                ->all(),
            'confidence' => $this->calculateConfidence($selectedImei, $selectedSerial, $barcodeValues, $combinedText),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractImeis(string $text): array
    {
        preg_match_all('/(?<!\d)(\d[\d\s-]{13,20}\d)(?!\d)/', strtoupper($text), $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $candidate): string => preg_replace('/\D+/', '', $candidate) ?? '')
            ->filter(fn (string $candidate): bool => strlen($candidate) === 15)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function extractSerials(string $text): array
    {
        $upperText = strtoupper($text);
        $serials = collect();

        preg_match_all('/(?:SERIAL(?:\s+NUMBER)?|S\/?N|SN)\s*[:#-]?\s*([A-Z0-9-]{5,40})/i', $upperText, $labelled);
        $serials = $serials->merge($labelled[1] ?? []);

        preg_match_all('/\b[A-Z0-9]{3,12}(?:-[A-Z0-9]{2,12}){1,4}\b/', $upperText, $hyphenated);
        $serials = $serials->merge($hyphenated[0] ?? []);

        return $serials
            ->map(fn (string $value): string => trim($value))
            ->filter(function (string $value): bool {
                if (strlen($value) < 6 || strlen($value) > 40) {
                    return false;
                }

                if (preg_match('/^\d{15}$/', $value) === 1) {
                    return false;
                }

                return preg_match('/[A-Z]/', $value) === 1 || preg_match('/-/', $value) === 1;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function pickBestImei(Collection $barcodeValues, string $combinedText, Collection $imeiCandidates): ?string
    {
        foreach ($barcodeValues as $barcode) {
            $barcodeImeis = $this->extractImeis($barcode);

            if ($barcodeImeis !== []) {
                return $barcodeImeis[0];
            }
        }

        if ($imeiCandidates->isEmpty()) {
            return null;
        }

        if (preg_match('/IMEI[^0-9]{0,8}(\d[\d\s-]{13,20}\d)/i', $combinedText, $match) === 1) {
            $candidate = preg_replace('/\D+/', '', $match[1]) ?: null;

            if ($candidate && strlen($candidate) === 15) {
                return $candidate;
            }
        }

        return $imeiCandidates->first();
    }

    private function pickBestSerial(Collection $barcodeValues, string $combinedText, Collection $serialCandidates): ?string
    {
        foreach ($barcodeValues as $barcode) {
            $barcodeSerials = $this->extractSerials($barcode);

            if ($barcodeSerials !== []) {
                return $barcodeSerials[0];
            }
        }

        if ($serialCandidates->isEmpty()) {
            return null;
        }

        if (preg_match('/(?:SERIAL(?:\s+NUMBER)?|S\/?N|SN)\s*[:#-]?\s*([A-Z0-9-]{5,40})/i', $combinedText, $match) === 1) {
            return trim(strtoupper($match[1]));
        }

        return $serialCandidates->first();
    }

    private function pickSecondImei(?string $selectedImei, Collection $imeiCandidates): ?string
    {
        if (! $selectedImei) {
            return null;
        }

        return $imeiCandidates
            ->first(fn (string $candidate): bool => $candidate !== $selectedImei);
    }

    private function calculateConfidence(?string $imei, ?string $serial, Collection $barcodeValues, string $combinedText): float
    {
        $confidence = 0.0;

        if ($imei) {
            $confidence += $barcodeValues->contains(fn (string $value): bool => in_array($imei, $this->extractImeis($value), true))
                ? 0.65
                : 0.4;
        }

        if ($serial) {
            $confidence += $barcodeValues->contains(fn (string $value): bool => in_array($serial, $this->extractSerials($value), true))
                ? 0.25
                : 0.15;
        }

        if ($imei && str_contains(strtoupper($combinedText), 'IMEI')) {
            $confidence += 0.08;
        }

        if ($serial && preg_match('/SERIAL|S\/?N|SN/i', $combinedText) === 1) {
            $confidence += 0.05;
        }

        return min(0.99, round($confidence, 2));
    }

    private function normalizeWhitespace(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
