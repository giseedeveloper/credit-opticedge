<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class KycPhoneService
{
    /**
     * @return array<int, array{
     *     iso: string,
     *     name: string,
     *     dial_code: string,
     *     flag: string,
     *     min_length: int,
     *     max_length: int
     * }>
     */
    public function supportedCountries(): array
    {
        return [
            ['iso' => 'TZ', 'name' => 'Tanzania', 'dial_code' => '+255', 'flag' => '🇹🇿', 'min_length' => 9, 'max_length' => 9],
            ['iso' => 'KE', 'name' => 'Kenya', 'dial_code' => '+254', 'flag' => '🇰🇪', 'min_length' => 9, 'max_length' => 9],
            ['iso' => 'UG', 'name' => 'Uganda', 'dial_code' => '+256', 'flag' => '🇺🇬', 'min_length' => 9, 'max_length' => 9],
            ['iso' => 'RW', 'name' => 'Rwanda', 'dial_code' => '+250', 'flag' => '🇷🇼', 'min_length' => 9, 'max_length' => 9],
            ['iso' => 'BI', 'name' => 'Burundi', 'dial_code' => '+257', 'flag' => '🇧🇮', 'min_length' => 8, 'max_length' => 8],
            ['iso' => 'ZM', 'name' => 'Zambia', 'dial_code' => '+260', 'flag' => '🇿🇲', 'min_length' => 9, 'max_length' => 9],
            ['iso' => 'MW', 'name' => 'Malawi', 'dial_code' => '+265', 'flag' => '🇲🇼', 'min_length' => 9, 'max_length' => 9],
            ['iso' => 'ZA', 'name' => 'South Africa', 'dial_code' => '+27', 'flag' => '🇿🇦', 'min_length' => 9, 'max_length' => 9],
            ['iso' => 'NG', 'name' => 'Nigeria', 'dial_code' => '+234', 'flag' => '🇳🇬', 'min_length' => 10, 'max_length' => 10],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function supportedCountryIsos(): array
    {
        return collect($this->supportedCountries())
            ->pluck('iso')
            ->all();
    }

    /**
     * @return array<string, array{
     *     iso: string,
     *     name: string,
     *     dial_code: string,
     *     flag: string,
     *     min_length: int,
     *     max_length: int
     * }>
     */
    public function countryMap(): array
    {
        return collect($this->supportedCountries())
            ->keyBy('iso')
            ->all();
    }

    /**
     * @return array{
     *     e164: string,
     *     country_iso: string,
     *     country_name: string,
     *     dial_code: string,
     *     flag: string,
     *     national_number: string,
     *     display: string
     * }|null
     */
    public function normalizeForField(
        string $field,
        string $countryField,
        ?string $rawPhone,
        ?string $countryIso,
        bool $required = true
    ): ?array {
        $phone = trim((string) $rawPhone);

        if ($phone === '') {
            if ($required) {
                throw ValidationException::withMessages([
                    $field => 'Enter a valid phone number.',
                ]);
            }

            return null;
        }

        $countries = $this->countryMap();
        $selectedCountry = $countries[(string) $countryIso] ?? null;

        if (! $selectedCountry) {
            throw ValidationException::withMessages([
                $countryField => 'Choose a valid country code.',
            ]);
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            throw ValidationException::withMessages([
                $field => 'Enter a valid phone number.',
            ]);
        }

        $country = $selectedCountry;
        $nationalNumber = $digits;

        if (str_starts_with($phone, '+')) {
            $detectedCountry = $this->matchCountryByDialDigits($digits);

            if ($detectedCountry) {
                $country = $detectedCountry;
                $nationalNumber = substr($digits, strlen(ltrim($country['dial_code'], '+')));
            }
        } else {
            $dialDigits = ltrim($selectedCountry['dial_code'], '+');

            if (str_starts_with($digits, $dialDigits) && strlen($digits) > strlen($dialDigits) + 5) {
                $nationalNumber = substr($digits, strlen($dialDigits));
            } else {
                $nationalNumber = ltrim($digits, '0');
            }
        }

        if ($nationalNumber === '') {
            throw ValidationException::withMessages([
                $field => 'Enter a valid phone number.',
            ]);
        }

        if (strlen($nationalNumber) < $country['min_length'] || strlen($nationalNumber) > $country['max_length']) {
            throw ValidationException::withMessages([
                $field => "Use a valid {$country['name']} phone number.",
            ]);
        }

        $dialCode = $country['dial_code'];
        $e164 = $dialCode.$nationalNumber;

        return [
            'e164' => $e164,
            'country_iso' => $country['iso'],
            'country_name' => $country['name'],
            'dial_code' => $dialCode,
            'flag' => $country['flag'],
            'national_number' => $nationalNumber,
            'display' => $this->formatDisplay($dialCode, $nationalNumber),
        ];
    }

    public function formatStoredPhone(?string $storedPhone, ?array $metadata = null): ?string
    {
        if (! $storedPhone) {
            return null;
        }

        if (is_array($metadata) && ($metadata['display'] ?? null)) {
            return (string) $metadata['display'];
        }

        $digits = preg_replace('/\D+/', '', $storedPhone) ?? '';

        if ($digits === '') {
            return $storedPhone;
        }

        $country = $this->matchCountryByDialDigits($digits);

        if (! $country) {
            return $storedPhone;
        }

        $nationalNumber = substr($digits, strlen(ltrim($country['dial_code'], '+')));

        return $this->formatDisplay($country['dial_code'], $nationalNumber);
    }

    /**
     * @return array{
     *     iso: string,
     *     name: string,
     *     dial_code: string,
     *     flag: string,
     *     min_length: int,
     *     max_length: int
     * }|null
     */
    private function matchCountryByDialDigits(string $digits): ?array
    {
        return collect($this->supportedCountries())
            ->sortByDesc(fn (array $country): int => strlen(ltrim($country['dial_code'], '+')))
            ->first(fn (array $country): bool => str_starts_with($digits, ltrim($country['dial_code'], '+')));
    }

    private function formatDisplay(string $dialCode, string $nationalNumber): string
    {
        if ($nationalNumber === '') {
            return $dialCode;
        }

        return trim($dialCode.' '.implode(' ', str_split($nationalNumber, 3)));
    }
}
