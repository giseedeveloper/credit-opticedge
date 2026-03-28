<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\InventoryUnit;
use App\Models\PhoneModel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class InventoryImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Require at least a model name and IMEI 1
        if (empty($row['model']) || empty($row['imei_1'])) {
            return null;
        }

        $brandName = trim($row['brand'] ?? 'Unknown');
        $modelName = trim($row['model']);

        // Cache or find/create Brand
        $brand = Brand::firstOrCreate(['name' => $brandName]);

        // Cache or find/create PhoneModel
        $phoneModel = PhoneModel::firstOrCreate(
            [
                'brand_id' => $brand->id,
                'name' => $modelName,
            ],
            [
                'device_type' => 'smartphone', // default assumption
            ]
        );

        return new InventoryUnit([
            'phone_model_id' => $phoneModel->id,
            'imei_1'         => $row['imei_1'],
            'imei_2'         => $row['imei_2'] ?? null,
            'serial_number'  => $row['serial_number'] ?? null,
            'purchase_price' => $row['purchase_price'] ?? 0,
            'status'         => 'hq_stock',
            'received_at'    => now(),
        ]);
    }

    public function rules(): array
    {
        return [
            'model'  => 'required|string',
            'imei_1' => 'required|string|unique:inventory_units,imei_1',
            // imei_2 must be unique as well, but nullable
            'imei_2' => 'nullable|string|unique:inventory_units,imei_2',
            'purchase_price' => 'nullable|numeric|min:0',
        ];
    }

    public function batchSize(): int
    {
        return 200;
    }
}


