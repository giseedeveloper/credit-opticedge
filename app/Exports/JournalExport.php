<?php

namespace App\Exports;

use App\Models\JournalEntry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JournalExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly string $dateFrom = '',
        private readonly string $dateTo   = ''
    ) {}

    public function collection()
    {
        return JournalEntry::with(['lines.account', 'creator'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->latest('date')
            ->get()
            ->flatMap(function ($entry) {
                return $entry->lines->map(fn ($line) => [
                    'Reference'   => $entry->reference,
                    'Date'        => $entry->date->format('d/m/Y'),
                    'Description' => $entry->description,
                    'Account'     => $line->account?->code.' – '.$line->account?->name,
                    'Type'        => $line->account?->type,
                    'Debit'       => number_format((float) $line->debit, 2),
                    'Credit'      => number_format((float) $line->credit, 2),
                    'Narration'   => $line->narration ?? '',
                    'Source'      => ucfirst($entry->source),
                    'Posted By'   => $entry->creator?->name ?? '—',
                ]);
            });
    }

    public function headings(): array
    {
        return ['Reference', 'Date', 'Description', 'Account', 'Type', 'Debit', 'Credit', 'Narration', 'Source', 'Posted By'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
