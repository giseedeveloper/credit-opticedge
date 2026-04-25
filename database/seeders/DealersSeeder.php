<?php

namespace Database\Seeders;

use App\Models\Dealer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DealersSeeder extends Seeder
{
    public function run(): void
    {
        $dealerData = [
            ['name' => 'TechHub Kariakoo', 'commission' => 4.5],
            ['name' => 'MobiDeals Arusha', 'commission' => 3.5],
            ['name' => 'SmartPhone Palace', 'commission' => 5.0],
            ['name' => 'Lake Zone Gadgets', 'commission' => 4.0],
            ['name' => 'Kili Mobile Shop', 'commission' => 3.0],
            ['name' => 'Capital City Electronics', 'commission' => 4.5],
            ['name' => 'Mlimani Phones & More', 'commission' => 3.5],
            ['name' => 'Posta Digital Mart', 'commission' => 4.0],
            ['name' => 'Mwananyamala Mobile Center', 'commission' => 3.0],
            ['name' => 'Ubungo Gadget House', 'commission' => 4.5],
            ['name' => 'Sinza Smart Devices', 'commission' => 3.5],
            ['name' => 'Mwenge Phone World', 'commission' => 4.0],
            ['name' => 'Sokoine Electronics', 'commission' => 3.0],
            ['name' => 'Nyerere Road Mobile Hub', 'commission' => 4.5],
            ['name' => 'Zanzibar Phone Plaza', 'commission' => 3.5],
        ];

        foreach ($dealerData as $idx => $v) {
            $code = 'VND-'.str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT);
            $name = (string) $v['name'];

            Dealer::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'phone' => '+255 76'.fake()->numerify('#######'),
                    'email' => Str::slug($name).'@dealer.co.tz',
                    'address' => 'Tanzania',
                    'tin_number' => fake()->numerify('###-###-###'),
                    'commission_rate' => (float) $v['commission'],
                    'status' => 'active',
                ]
            );
        }
    }
}
