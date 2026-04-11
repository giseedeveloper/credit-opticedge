<?php

namespace App\Exports;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DelinquencyExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Loan::with(['customer', 'inventoryUnit'])
            ->whereIn('ifrs_stage', [2, 3])
            ->get();
    }

    public function headings(): array
    {
        return [
            'Loan Contract Ref',
            'Customer First Name',
            'Customer Last Name',
            'NIDA ID',
            'Base Cost (Principal)',
            'Remaining Balance',
            'Status',
            'IFRS 9 Default Stage',
            'Days Past Due (DPD)',
            'Linked Hardware Asset Tag',
            'Disbursement Date',
        ];
    }

    public function map($loan): array
    {
        return [
            $loan->loan_number,
            $loan->customer->first_name ?? 'N/A',
            $loan->customer->last_name ?? 'N/A',
            $loan->customer->nida_number ?? 'N/A',
            $loan->principal_amount,
            $loan->remaining_balance,
            $loan->status,
            'Stage '.($loan->ifrs_stage ?? 'N/A'),
            $loan->dpd,
            $loan->inventoryUnit->imei_1 ?? 'N/A',
            $loan->disbursed_at ? $loan->disbursed_at->format('Y-m-d') : 'N/A',
        ];
    }
}
