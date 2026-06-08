<?php

namespace App\Services;

use App\Models\PhoneModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KycDeviceCatalogMatcher
{
    private const float AUTO_SELECT_CONFIDENCE = 0.62;

    /**
     * @param  array{
     *     detected_model_text?: ?string,
     *     detected_model_code?: ?string,
     *     detected_ram?: ?string,
     *     detected_storage?: ?string,
     *     raw_text?: ?string
     * }  $scan
     * @param  Collection<int, PhoneModel>|null  $models
     * @return array{
     *     brand_id: ?string,
     *     phone_model_id: ?string,
     *     brand_name: ?string,
     *     model_name: ?string,
     *     confidence: float,
     *     auto_selected: bool,
     *     match_reason: ?string
     * }
     */
    public function matchFromScan(array $scan, ?Collection $models = null): array
    {
        $haystack = $this->buildHaystack($scan);

        if ($haystack === '') {
            return $this->emptyResult();
        }

        $models ??= PhoneModel::query()
            ->with('brand')
            ->where('is_active', true)
            ->get();

        $best = null;
        $bestScore = 0.0;

        foreach ($models as $model) {
            $score = $this->scoreModel($model, $scan, $haystack);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $model;
            }
        }

        if (! $best instanceof PhoneModel || $bestScore < 0.35) {
            return $this->emptyResult();
        }

        return [
            'brand_id' => (string) $best->brand_id,
            'phone_model_id' => (string) $best->id,
            'brand_name' => $best->brand?->name,
            'model_name' => $best->name,
            'confidence' => round($bestScore, 2),
            'auto_selected' => $bestScore >= self::AUTO_SELECT_CONFIDENCE,
            'match_reason' => $this->describeMatch($best, $scan),
        ];
    }

    /**
     * @param  array<string, mixed>  $scan
     */
    private function buildHaystack(array $scan): string
    {
        return Str::upper(collect([
            $scan['detected_model_text'] ?? null,
            $scan['detected_model_code'] ?? null,
            $scan['detected_ram'] ?? null,
            $scan['detected_storage'] ?? null,
            $scan['raw_text'] ?? null,
        ])->filter()->implode(' '));
    }

    /**
     * @param  array<string, mixed>  $scan
     */
    private function scoreModel(PhoneModel $model, array $scan, string $haystack): float
    {
        $score = 0.0;
        $brandName = Str::upper((string) ($model->brand?->name ?? ''));
        $modelName = Str::upper((string) $model->name);
        $modelCode = Str::upper((string) ($scan['detected_model_code'] ?? ''));

        if ($brandName !== '' && str_contains($haystack, $brandName)) {
            $score += 0.28;
        }

        if ($modelName !== '' && str_contains($haystack, $modelName)) {
            $score += 0.34;
        }

        foreach ($this->modelTokens($modelName) as $token) {
            if (strlen($token) >= 3 && str_contains($haystack, $token)) {
                $score += 0.08;
            }
        }

        if ($modelCode !== '') {
            $specCode = Str::upper((string) data_get($model->specifications, 'model_code', ''));
            if ($specCode !== '' && $specCode === $modelCode) {
                $score += 0.35;
            } elseif (str_contains($haystack, $modelCode)) {
                $score += 0.22;
            }
        }

        $specs = $model->specifications ?? [];
        $ram = Str::upper((string) ($scan['detected_ram'] ?? ''));
        $storage = Str::upper((string) ($scan['detected_storage'] ?? ''));
        $specRam = Str::upper((string) ($specs['ram'] ?? ''));
        $specStorage = Str::upper((string) ($specs['storage'] ?? ''));

        if ($ram !== '' && $specRam !== '' && $ram === $specRam) {
            $score += 0.08;
        }

        if ($storage !== '' && $specStorage !== '' && $storage === $specStorage) {
            $score += 0.08;
        }

        return min(1.0, $score);
    }

    /**
     * @return array<int, string>
     */
    private function modelTokens(string $modelName): array
    {
        return collect(preg_split('/[\s\-\/]+/', $modelName) ?: [])
            ->filter(fn (string $token): bool => strlen($token) >= 3)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $scan
     */
    private function describeMatch(PhoneModel $model, array $scan): string
    {
        if (filled($scan['detected_model_code'] ?? null)) {
            return 'Matched catalog model from detected model code.';
        }

        if (filled($scan['detected_model_text'] ?? null)) {
            return 'Matched catalog model from OCR model text.';
        }

        return 'Matched catalog model from scan text.';
    }

    /**
     * @return array{
     *     brand_id: null,
     *     phone_model_id: null,
     *     brand_name: null,
     *     model_name: null,
     *     confidence: float,
     *     auto_selected: bool,
     *     match_reason: null
     * }
     */
    private function emptyResult(): array
    {
        return [
            'brand_id' => null,
            'phone_model_id' => null,
            'brand_name' => null,
            'model_name' => null,
            'confidence' => 0.0,
            'auto_selected' => false,
            'match_reason' => null,
        ];
    }
}
