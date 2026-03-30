<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 30px; }
        h1 { font-size: 18px; font-weight: 900; color: #2563eb; margin: 0 0 4px; }
        .sub { font-size: 10px; color: #6b7280; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead tr { background: #1a2035; color: #fff; }
        th { padding: 8px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        th.right, td.right { text-align: right; }
        td { padding: 7px 12px; border-bottom: 1px solid #e5e7eb; }
        .section-head { background: #f3f4f6; font-weight: 700; font-size: 11px; color: #374151; }
        .total-row td { font-weight: 900; background: #ede9fe; color: #2563eb; }
        .net-row td { font-weight: 900; font-size: 13px; }
        .profit td { background: #d1fae5; color: #065f46; }
        .loss td { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 30px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .code { font-family: monospace; font-weight: 700; color: #2563eb; }
    </style>
</head>
<body>
    <h1>Profit & Loss Statement</h1>
    <p class="sub">
        Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        &nbsp;&middot;&nbsp; Generated: {{ now()->format('d M Y H:i') }}
        &nbsp;&middot;&nbsp; Opticedge Credit Ltd
    </p>

    {{-- Revenue --}}
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Revenue Account</th>
                <th class="right">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($revenue as $r)
            <tr>
                <td class="code">{{ $r['code'] }}</td>
                <td>{{ $r['name'] }}</td>
                <td class="right">{{ number_format((float)$r['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;color:#9ca3af">No revenue accounts</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2">Total Revenue</td>
                <td class="right">{{ number_format((float)$totalRevenue, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Expenses --}}
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Expense Account</th>
                <th class="right">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $e)
            <tr>
                <td class="code">{{ $e['code'] }}</td>
                <td>{{ $e['name'] }}</td>
                <td class="right">{{ number_format((float)$e['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;color:#9ca3af">No expense accounts</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2">Total Expenses</td>
                <td class="right">{{ number_format((float)$totalExpense, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Net --}}
    @php $np = (float)$netProfit; @endphp
    <table>
        <tbody>
            <tr class="net-row {{ $np >= 0 ? 'profit' : 'loss' }}">
                <td>Net {{ $np >= 0 ? 'Profit' : 'Loss' }}</td>
                <td class="right">{{ $np >= 0 ? '' : '(' }}TZS {{ number_format(abs($np), 2) }}{{ $np >= 0 ? '' : ')' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        This report was generated automatically by Opticedge Credit Accounting Workspace.
        All values are in Tanzanian Shillings (TZS). &copy; {{ date('Y') }} Opticedge Credit Ltd.
    </div>
</body>
</html>
