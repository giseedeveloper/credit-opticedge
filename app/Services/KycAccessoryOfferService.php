<?php

namespace App\Services;

use Illuminate\Support\Str;

class KycAccessoryOfferService
{
    /**
     * @return array<int, array{
     *     code: string,
     *     name: string,
     *     default_offer_type: string,
     *     note_hint: string
     * }>
     */
    public function presetOptions(): array
    {
        return [
            ['code' => 'screen_protector', 'name' => 'Screen Protector', 'default_offer_type' => 'free', 'note_hint' => 'Applied during handover'],
            ['code' => 'phone_cover', 'name' => 'Phone Cover', 'default_offer_type' => 'free', 'note_hint' => 'Protective cover offered by the shop'],
            ['code' => 'charger', 'name' => 'Extra Charger', 'default_offer_type' => 'charged', 'note_hint' => 'Sold as an add-on item'],
            ['code' => 'earphones', 'name' => 'Earphones', 'default_offer_type' => 'charged', 'note_hint' => 'Optional accessory'],
            ['code' => 'memory_card', 'name' => 'Memory Card', 'default_offer_type' => 'discounted', 'note_hint' => 'Promotion or bundle discount'],
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     quantity: int,
     *     offer_type: string,
     *     unit_price: string,
     *     notes: string
     * }
     */
    public function blankItem(): array
    {
        return [
            'code' => '',
            'name' => '',
            'quantity' => 1,
            'offer_type' => 'free',
            'unit_price' => '',
            'notes' => '',
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     quantity: int,
     *     offer_type: string,
     *     unit_price: string,
     *     notes: string
     * }
     */
    public function presetItem(string $code): array
    {
        $preset = collect($this->presetOptions())->firstWhere('code', $code);

        if (! $preset) {
            return $this->blankItem();
        }

        return [
            'code' => $preset['code'],
            'name' => $preset['name'],
            'quantity' => 1,
            'offer_type' => $preset['default_offer_type'],
            'unit_price' => '',
            'notes' => $preset['note_hint'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{
     *     code: string,
     *     name: string,
     *     quantity: int,
     *     offer_type: string,
     *     unit_price: float|null,
     *     notes: string|null
     * }>
     */
    public function normalize(array $items): array
    {
        return collect($items)
            ->map(function (array $item): ?array {
                $name = trim((string) ($item['name'] ?? ''));
                $notes = trim((string) ($item['notes'] ?? ''));
                $code = trim((string) ($item['code'] ?? ''));

                if ($name === '' && $notes === '' && $code === '') {
                    return null;
                }

                $offerType = in_array(($item['offer_type'] ?? 'free'), ['free', 'charged', 'discounted'], true)
                    ? (string) $item['offer_type']
                    : 'free';
                $unitPrice = trim((string) ($item['unit_price'] ?? ''));

                return [
                    'code' => $code !== '' ? $code : Str::slug($name ?: 'custom-accessory'),
                    'name' => $name,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'offer_type' => $offerType,
                    'unit_price' => $offerType === 'free' || $unitPrice === '' ? null : round((float) $unitPrice, 2),
                    'notes' => $notes !== '' ? $notes : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
