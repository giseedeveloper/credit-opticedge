<?php

namespace App\Services;

use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DocumentService
{
    /**
     * Generate legal loan agreement using the current loan/device schema.
     */
    public function generateLoanAgreement(Loan $loan): Response
    {
        $loan->load(['customer', 'inventoryUnit.phoneModel.brand', 'repaymentSchedules']);

        $pdf = Pdf::loadView('pdf.loan-agreement', [
            'loan' => $loan,
            'customer' => $loan->customer,
            'unit' => $loan->inventoryUnit,
            'schedules' => $loan->repaymentSchedules,
            'dateGenerated' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->download("Opticedge_Agreement_{$loan->loan_number}.pdf");
    }
}
