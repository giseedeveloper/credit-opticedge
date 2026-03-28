<?php

namespace App\Exports;

use App\Models\JournalEntryLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly string $accountId = '') {}

    public function collection()
    {
        $running = '0.0000';

        return JournalEntryLine::with(['journalEntry', 'account'])
            ->when($this->accountId, fn ($q) => $q->where('account_id', $this->accountId))
            ->whereHas('journalEntry')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entry_lines.created_at')
            ->select('journal_entry_lines.*')
            ->get()
            ->map(function ($line) use (&$running) {
                $running = bcadd(bcadd($running, (string) $line->debit, 4), '0', 4);
                $running = bcsub($running, (string) $line->credit, 4);

                return [
                    'Date'        => $line->journalEntry?->date?->format('d/m/Y'),
                    'Reference'   => $line->journalEntry?->reference,
                    'Description' => $line->journalEntry?->description,
                    'Narration'   => $line->narration ?? '',
                    'Debit'       => number_format((float) $line->debit, 2),
                    'Credit'      => number_format((float) $line->credit, 2),
                    'Balance'     => number_format((float) $running, 2),
                ];
            });
    }

    public function headings(): array
    {
        return ['Date', 'Reference', 'Description', 'Narration', 'Debit (TZS)', 'Credit (TZS)', 'Running Balance (TZS)'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
