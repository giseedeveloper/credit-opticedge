<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CollectionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return DB::table('transactions')
            ->join('loans', 'transactions.loan_id', '=', 'loans.id')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('transactions.type', 'repayment')
            ->where('transactions.entry_type', 'credit')
            ->select(
                'transactions.transacted_at as paid_at',
                'customers.first_name',
                'customers.last_name',
                'customers.nida_number',
                'transactions.amount',
                'transactions.channel',
                'transactions.reference'
            )
            ->orderByDesc('transactions.transacted_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date Settled',
            'First Name',
            'Last Name',
            'NIDA ID',
            'Amount Collected (TZS)',
            'Remittance Method',
            'Transaction Reference',
        ];
    }

    public function map($row): array
    {
        return [
            $row->paid_at,
            $row->first_name,
            $row->last_name,
            $row->nida_number,
            $row->amount,
            $row->channel,
            $row->reference,
        ];
    }
}
