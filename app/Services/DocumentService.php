<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Loan;

class DocumentService
{
    /**
     * Generate Legal Loan Agreement mapping customer Spatie Media bounds.
     */
    public function generateLoanAgreement(Loan $loan)
    {
        $loan->load(['customer', 'inventoryUnit.brandModel.brand', 'repaymentSchedules']);

        $pdf = Pdf::loadView('pdf.loan-agreement', [
            'loan' => $loan,
            'customer' => $loan->customer,
            'unit' => $loan->inventoryUnit,
            'schedules' => $loan->repaymentSchedules,
            'dateGenerated' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->download("Opticedge_Agreement_{$loan->contract_number}.pdf");
    }
}
