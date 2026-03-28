<?php

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\DB;

class CollectionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return DB::table('repayment_schedules')
                 ->join('loans', 'repayment_schedules.loan_id', '=', 'loans.id')
                 ->join('customers', 'loans.customer_id', '=', 'customers.id')
                 ->where('repayment_schedules.status', 'paid')
                 ->select(
                     'repayment_schedules.paid_date',
                     'customers.first_name',
                     'customers.last_name',
                     'customers.nida_number',
                     'repayment_schedules.amount_paid',
                     'repayment_schedules.payment_method'
                 )
                 ->orderBy('repayment_schedules.paid_date', 'desc')
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
            'Remittance Method'
        ];
    }

    public function map($row): array
    {
        return [
            $row->paid_date,
            $row->first_name,
            $row->last_name,
            $row->nida_number,
            $row->amount_paid,
            $row->payment_method,
        ];
    }
}
