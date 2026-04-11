<?php

namespace App\Exports;

use App\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return InventoryUnit::with(['phoneModel.brand', 'vendor', 'branch'])->get();
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
            'Physical Grading',
        ];
    }

    public function map($unit): array
    {
        return [
            $unit->id,
            $unit->phoneModel->brand->name ?? 'N/A',
            $unit->phoneModel->name ?? 'N/A',
            $unit->imei_1,
            $unit->imei_2,
            $unit->purchase_price,
            $unit->status,
            $unit->vendor->name ?? $unit->branch->name ?? 'Central HQ Vault',
            $unit->grading ?? 'Brand New',
        ];
    }
}
