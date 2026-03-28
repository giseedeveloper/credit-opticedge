<?php

namespace App\Exports;

use App\Models\InventoryUnit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return InventoryUnit::with(['brandModel.brand', 'vendor'])->get();
    }

    public function headings(): array
    {
        return [
            'Asset Tag / UUID',
            'Hardware OEM',
            'Model Configuration',
            'IMEI 1',
            'IMEI 2',
            'Acquisition Base Cost',
            'Asset Status',
            'Branch / Vendor Assignment',
            'Physical Grading'
        ];
    }

    public function map($unit): array
    {
        return [
            $unit->id,
            $unit->brandModel->brand->name ?? 'N/A',
            $unit->brandModel->name ?? 'N/A',
            $unit->imei_1,
            $unit->imei_2,
            $unit->cost_price,
            $unit->status,
            $unit->vendor->shop_name ?? 'Central HQ Vault',
            $unit->grading ?? 'Brand New',
        ];
    }
}
