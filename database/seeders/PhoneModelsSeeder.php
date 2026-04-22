<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\PhoneModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PhoneModelsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'brand' => 'Samsung',
                'name' => 'Galaxy A15 128GB/4GB',
                'retail_price' => 520000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.5"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Samsung',
                'name' => 'Galaxy A05s 128GB/4GB',
                'retail_price' => 420000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.7"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Samsung',
                'name' => 'Galaxy A25 256GB/8GB',
                'retail_price' => 850000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.5"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Tecno',
                'name' => 'Spark 10 128GB/8GB',
                'retail_price' => 420000,
                'specs' => ['ram' => '8GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.6"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Tecno',
                'name' => 'Spark 20 256GB/8GB',
                'retail_price' => 620000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.6"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Tecno',
                'name' => 'Camon 20 256GB/8GB',
                'retail_price' => 780000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.67"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Infinix',
                'name' => 'Hot 30 128GB/8GB',
                'retail_price' => 450000,
                'specs' => ['ram' => '8GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.8"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Infinix',
                'name' => 'Smart 8 128GB/4GB',
                'retail_price' => 320000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.6"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Infinix',
                'name' => 'Note 30 256GB/8GB',
                'retail_price' => 780000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.78"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Apple',
                'name' => 'iPhone 11 128GB',
                'retail_price' => 980000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'iOS', 'screen' => '6.1"', 'battery' => '3110mAh'],
            ],
            [
                'brand' => 'Apple',
                'name' => 'iPhone XR 128GB',
                'retail_price' => 780000,
                'specs' => ['ram' => '3GB', 'storage' => '128GB', 'os' => 'iOS', 'screen' => '6.1"', 'battery' => '2942mAh'],
            ],
            [
                'brand' => 'Xiaomi',
                'name' => 'Redmi Note 13 256GB/8GB',
                'retail_price' => 720000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.67"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Xiaomi',
                'name' => 'Redmi 13C 128GB/4GB',
                'retail_price' => 380000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.74"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Xiaomi',
                'name' => 'POCO C65 256GB/8GB',
                'retail_price' => 520000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.74"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'OPPO',
                'name' => 'A18 128GB/4GB',
                'retail_price' => 410000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.56"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'OPPO',
                'name' => 'A38 128GB/4GB',
                'retail_price' => 480000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.56"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'vivo',
                'name' => 'Y27 128GB/6GB',
                'retail_price' => 640000,
                'specs' => ['ram' => '6GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.64"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'vivo',
                'name' => 'Y17s 128GB/4GB',
                'retail_price' => 450000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.56"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'itel',
                'name' => 'A70 128GB/4GB',
                'retail_price' => 280000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.6"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'itel',
                'name' => 'S23 128GB/8GB',
                'retail_price' => 390000,
                'specs' => ['ram' => '8GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.78"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'Nokia',
                'name' => 'G22 128GB/4GB',
                'retail_price' => 520000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.52"', 'battery' => '5050mAh'],
            ],
            [
                'brand' => 'Nokia',
                'name' => 'C32 128GB/4GB',
                'retail_price' => 380000,
                'specs' => ['ram' => '4GB', 'storage' => '128GB', 'os' => 'Android', 'screen' => '6.52"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'realme',
                'name' => 'C53 256GB/8GB',
                'retail_price' => 520000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.74"', 'battery' => '5000mAh'],
            ],
            [
                'brand' => 'realme',
                'name' => 'C55 256GB/8GB',
                'retail_price' => 650000,
                'specs' => ['ram' => '8GB', 'storage' => '256GB', 'os' => 'Android', 'screen' => '6.72"', 'battery' => '5000mAh'],
            ],
        ];

        foreach ($items as $item) {
            $brandName = trim((string) $item['brand']);
            $brand = Brand::query()->firstOrCreate(
                ['slug' => Str::slug($brandName)],
                ['name' => $brandName, 'is_active' => true]
            );

            $modelName = trim((string) $item['name']);
            $slug = Str::slug($brand->name.' '.$modelName);

            PhoneModel::query()->updateOrCreate(
                ['brand_id' => $brand->id, 'name' => $modelName],
                [
                    'slug' => $slug,
                    'retail_price' => (float) $item['retail_price'],
                    'cost_price' => 0,
                    'specifications' => $item['specs'],
                    'is_active' => true,
                ]
            );
        }
    }
}
