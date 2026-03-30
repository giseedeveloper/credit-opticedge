<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Opticedge Credit - Loan Agreement</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; font-size: 14px; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 20px; }
        .logo-text { color: #2563eb; font-size: 24px; font-weight: bold; }
        .section { margin-bottom: 20px; }
        .section h3 { background-color: #E5E4E2; padding: 10px; margin-bottom: 10px; color: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f9fafb; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
        .page-break { page-break-after: always; }
        /* Mock Selfie Box */
        .photo-box { width: 100px; height: 100px; border: 1px dashed #2563eb; display: inline-block; vertical-align: top; text-align: center; line-height: 100px; font-size: 10px; color: #999; }
        .sign-area { margin-top: 40px; }
        .sign-line { border-bottom: 1px solid #333; width: 250px; display: inline-block; margin-top: 30px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-text">Opticedge Credit Limited</div>
        <p>123 Financial District, Dar es Salaam, Tanzania | Tel: +255 700 000 000</p>
        <h2>DEVICE FINANCING & LEASE AGREEMENT</h2>
        <p>Contract Number: <strong>{{ $loan->id ?? 'XXXX-XXXX-XXXX' }}</strong></p>
        <p>Date Generated: {{ $dateGenerated }}</p>
    </div>

    <div class="section">
        <h3>1. Customer Details (The Lessee)</h3>
        <div style="display: inline-block; width: 70%;">
            <p><strong>Name:</strong> {{ $customer->first_name ?? 'N/A' }} {{ $customer->last_name ?? 'N/A' }}</p>
            <p><strong>NIDA ID:</strong> {{ $customer->nida_number ?? 'N/A' }}</p>
            <p><strong>Phone:</strong> {{ $customer->phone ?? 'N/A' }}</p>
        </div>
        <div class="photo-box">
            [Customer Selfie]
        </div>
    </div>

    <div class="section">
        <h3>2. Financed Asset (The Device)</h3>
        <table>
            <tr>
                <th>Brand / Model</th>
                <th>IMEI 1</th>
                <th>IMEI 2</th>
                <th>Condition</th>
            </tr>
            <tr>
                <td>{{ $unit->brandModel->brand->name ?? 'Unknown' }} {{ $unit->brandModel->name ?? 'Model' }}</td>
                <td>{{ $unit->imei_1 ?? 'N/A' }}</td>
                <td>{{ $unit->imei_2 ?? 'N/A' }}</td>
                <td>{{ $unit->grading ?? 'Brand New' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>3. Commercial Terms & Amortization</h3>
        <table>
            <tr>
                <td><strong>Capital Financed:</strong></td>
                <td>TZS {{ number_format($loan->principal_amount ?? 0, 2) }}</td>
                <td><strong>Total Interest & Fees:</strong></td>
                <td>TZS {{ number_format(($loan->total_amount_due ?? 0) - ($loan->principal_amount ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Payable:</strong></td>
                <td>TZS {{ number_format($loan->total_amount_due ?? 0, 2) }}</td>
                <td><strong>Duration:</strong></td>
                <td>{{ count($schedules ?? []) }} Installments</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>4. Legal Covenants & Restrictions</h3>
        <p style="font-size: 11px; text-align: justify; line-height: 1.4;">
            This Legal Agreement bindings the Customer ("Lessee") to Opticedge Credit Limited ("Lessor"). The Lessee explicitly authorizes the Lessor to deploy native Mobile Device Management (MDM) hooks (including but not limited to Samsung Knox and Google Device Lock). Failure to settle weekly or monthly amortized installments exceeding a grace period of three (3) days will immediately and irrevocably trigger a remote, cryptographic lockout of the physical hardware preventing cellular and standard UX operations over Android OS frameworks until arrears are fully remediated.
        </p>
    </div>

    <div class="sign-area">
        <table style="border: none; width: 100%;">
            <tr style="border: none;">
                <td style="border: none; width: 50%;">
                    <p><strong>On Behalf of Opticedge Credit:</strong></p>
                    <div class="sign-line"></div>
                    <p>Authorized Signature & Stamp</p>
                </td>
                <td style="border: none; width: 50%;">
                    <p><strong>Customer (Lessee):</strong></p>
                    <div class="sign-line"></div>
                    <p>Signature / Thumbprint</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Page 1 of 1 | Opticedge Credit Internal Systems | Strict IFRS 9 Matrix Compliance.
    </div>

</body>
</html>
