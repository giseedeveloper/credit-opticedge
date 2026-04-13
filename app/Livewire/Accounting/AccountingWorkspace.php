<?php

namespace App\Livewire\Accounting;

use App\Exports\JournalExport;
use App\Exports\LedgerExport;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Title('Accounting Workspace')]
class AccountingWorkspace extends Component
{
    use WithFileUploads, WithPagination;

    public string $activeTab = 'overview';

    // ── Chart of Accounts ──────────────────────────────────
    public string $accountSearch = '';

    public string $accountTypeFilter = '';

    public bool $showAccountModal = false;

    public string $acCode = '';

    public string $acName = '';

    public string $acType = '';

    public string $acDescription = '';

    public ?string $editAccountId = null;

    // ── Journal ────────────────────────────────────────────
    public string $journalSearch = '';

    public string $journalDateFrom = '';

    public string $journalDateTo = '';

    public bool $showJournalModal = false;

    public string $jeReference = '';

    public string $jeDate = '';

    public string $jeDescription = '';

    /** @var array<int,array{account_id:string,debit:string,credit:string,narration:string}> */
    public array $jeLines = [];

    // ── Journal Detail ─────────────────────────────────────
    public ?string $selectedJournalId = null;

    public bool $showJournalDetail = false;

    // ── Ledger ─────────────────────────────────────────────
    public string $ledgerAccountId = '';

    public string $ledgerDateFrom = '';

    public string $ledgerDateTo = '';

    // ── P&L ────────────────────────────────────────────────
    public string $plDateFrom = '';

    public string $plDateTo = '';

    // ── Selcom Import ──────────────────────────────────────
    public mixed $selcomFile = null;

    public array $parsedRows = [];

    public bool $parseError = false;

    public string $parseMessage = '';

    // ── Reconciliation ─────────────────────────────────────
    public ?string $recoImportId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.view'), 403);
        $this->jeDate = now()->toDateString();
        $this->plDateFrom = now()->startOfMonth()->toDateString();
        $this->plDateTo = now()->toDateString();
        $this->addLine();
        $this->addLine();
    }

    // ── Tab ────────────────────────────────────────────────
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ── Page resets ────────────────────────────────────────
    public function updatedAccountSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAccountTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedJournalSearch(): void
    {
        $this->resetPage();
    }

    public function updatedJournalDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedJournalDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedLedgerAccountId(): void
    {
        $this->ledgerDateFrom = '';
        $this->ledgerDateTo = '';
    }

    // ── Journal Detail ─────────────────────────────────────
    public function viewJournalEntry(string $id): void
    {
        $this->selectedJournalId = $id;
        $this->showJournalDetail = true;
    }

    public function closeJournalDetail(): void
    {
        $this->showJournalDetail = false;
        $this->selectedJournalId = null;
    }

    // ── Account Toggle ─────────────────────────────────────
    public function toggleAccountStatus(string $id): void
    {
        abort_unless(auth()->user()->canAccess('accounting.create'), 403);
        $acc = Account::findOrFail($id);
        $acc->update(['is_active' => ! $acc->is_active]);
        $this->dispatch('toast', message: 'Account '.($acc->is_active ? 'activated' : 'deactivated').'.', type: 'success');
    }

    // ── Chart of Accounts ──────────────────────────────────
    public function openAccountModal(?string $id = null): void
    {
        abort_unless(auth()->user()->canAccess('accounting.create'), 403);
        $this->resetAccountForm();
        if ($id) {
            $acc = Account::findOrFail($id);
            $this->editAccountId = $id;
            $this->acCode = $acc->code;
            $this->acName = $acc->name;
            $this->acType = $acc->type;
            $this->acDescription = $acc->description ?? '';
        }
        $this->showAccountModal = true;
    }

    public function saveAccount(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.create'), 403);

        $this->validate([
            'acCode' => 'required|string|max:20|unique:accounts,code'.($this->editAccountId ? ','.$this->editAccountId : ''),
            'acName' => 'required|string|max:120',
            'acType' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
        ]);

        $data = [
            'code' => trim($this->acCode),
            'name' => trim($this->acName),
            'type' => $this->acType,
            'description' => trim($this->acDescription),
        ];

        if ($this->editAccountId) {
            Account::findOrFail($this->editAccountId)->update($data);
            $this->dispatch('toast', message: 'Account updated.', type: 'success');
        } else {
            Account::create($data);
            $this->dispatch('toast', message: 'Account created.', type: 'success');
        }

        $this->resetAccountForm();
        $this->showAccountModal = false;
    }

    private function resetAccountForm(): void
    {
        $this->acCode = '';
        $this->acName = '';
        $this->acType = '';
        $this->acDescription = '';
        $this->editAccountId = null;
        $this->resetValidation();
    }

    // ── Journal Entry ──────────────────────────────────────
    public function addLine(): void
    {
        $this->jeLines[] = ['account_id' => '', 'debit' => '', 'credit' => '', 'narration' => ''];
    }

    public function removeLine(int $index): void
    {
        if (count($this->jeLines) > 2) {
            array_splice($this->jeLines, $index, 1);
            $this->jeLines = array_values($this->jeLines);
        }
    }

    public function openJournalModal(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.create'), 403);
        $this->jeReference = 'JNL-'.strtoupper(substr(uniqid(), -6));
        $this->jeDate = now()->toDateString();
        $this->jeDescription = '';
        $this->jeLines = [];
        $this->addLine();
        $this->addLine();
        $this->showJournalModal = true;
    }

    public function saveJournalEntry(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.create'), 403);

        $this->validate([
            'jeReference' => 'required|string|unique:journal_entries,reference',
            'jeDate' => 'required|date',
            'jeDescription' => 'required|string|max:255',
            'jeLines' => 'required|array|min:2',
            'jeLines.*.account_id' => 'required|exists:accounts,id',
        ]);

        $totalDebits = '0.0000';
        $totalCredits = '0.0000';

        foreach ($this->jeLines as $line) {
            $totalDebits = bcadd($totalDebits, (string) ($line['debit'] ?: 0), 4);
            $totalCredits = bcadd($totalCredits, (string) ($line['credit'] ?: 0), 4);
        }

        if (bccomp($totalDebits, $totalCredits, 4) !== 0) {
            $this->addError('jeLines', 'Total Debits ('.number_format((float) $totalDebits, 2).') must equal Total Credits ('.number_format((float) $totalCredits, 2).').');

            return;
        }

        DB::transaction(function () {
            $entry = JournalEntry::create([
                'reference' => $this->jeReference,
                'date' => $this->jeDate,
                'description' => $this->jeDescription,
                'source' => 'manual',
                'created_by' => auth()->id(),
            ]);

            foreach ($this->jeLines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?: 0,
                    'credit' => $line['credit'] ?: 0,
                    'narration' => $line['narration'] ?? null,
                ]);

                Account::where('id', $line['account_id'])->update([
                    'balance' => DB::raw('balance + '.((float) ($line['debit'] ?: 0) - (float) ($line['credit'] ?: 0))),
                ]);
            }

            activity('accounting')
                ->causedBy(auth()->user())
                ->performedOn($entry)
                ->withProperties(['reference' => $entry->reference, 'total' => $this->totalDebits()])
                ->log('Journal entry posted: '.$entry->reference);
        });

        $this->showJournalModal = false;
        $this->dispatch('toast', message: 'Journal entry posted successfully.', type: 'success');
    }

    private function totalDebits(): string
    {
        $total = '0.0000';
        foreach ($this->jeLines as $line) {
            $total = bcadd($total, (string) ($line['debit'] ?: 0), 4);
        }

        return $total;
    }

    // ── Selcom CSV Import ──────────────────────────────────
    public function parseSelcomStatement(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.create'), 403);

        $this->validate(['selcomFile' => 'required|file|mimes:csv,txt|max:5120']);

        $this->parsedRows = [];
        $this->parseError = false;
        $this->parseMessage = '';

        try {
            $path = $this->selcomFile->getRealPath();
            $handle = fopen($path, 'r');

            if (! $handle) {
                throw new \RuntimeException('Unable to read file.');
            }

            $headers = null;
            $rows = [];
            $lineNo = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNo++;
                if ($lineNo === 1) {
                    $headers = array_map('trim', $row);

                    continue;
                }
                if (empty(array_filter($row))) {
                    continue;
                }
                $rows[] = $headers
                    ? array_combine($headers, array_map('trim', $row))
                    : $row;
            }

            fclose($handle);
            $this->parsedRows = $rows;
            $this->parseMessage = count($rows).' rows parsed successfully.';
        } catch (\Throwable $e) {
            $this->parseError = true;
            $this->parseMessage = 'Parse error: '.$e->getMessage();
        }
    }

    public function postSelcomToLedger(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.create'), 403);

        if (empty($this->parsedRows)) {
            $this->dispatch('toast', message: 'No parsed rows to post.', type: 'danger');

            return;
        }

        $selcomAccount = Account::whereInsensitiveLike('name', '%selcom%')->first();
        $cashAccount = Account::whereInsensitiveLike('name', '%cash%')->orWhere('code', '1000')->first();

        if (! $selcomAccount || ! $cashAccount) {
            $this->dispatch('toast', message: 'Selcom or Cash account not found in Chart of Accounts.', type: 'danger');

            return;
        }

        DB::transaction(function () use ($selcomAccount, $cashAccount) {
            foreach ($this->parsedRows as $i => $row) {
                $amount = (float) preg_replace('/[^0-9.]/', '', $row['Amount'] ?? $row['amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $ref = 'SELC-'.now()->format('Ymd').'-'.($i + 1);
                $date = $row['Date'] ?? $row['date'] ?? now()->toDateString();

                $entry = JournalEntry::create([
                    'reference' => $ref,
                    'date' => $date,
                    'description' => $row['Description'] ?? $row['description'] ?? 'Selcom Import',
                    'source' => 'selcom_import',
                    'created_by' => auth()->id(),
                ]);

                JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $cashAccount->id,   'debit' => $amount, 'credit' => 0, 'narration' => 'Selcom receipt']);
                JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $selcomAccount->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Selcom receipt']);
            }
        });

        $this->parsedRows = [];
        $this->selcomFile = null;
        $this->dispatch('toast', message: 'Selcom data posted to ledger.', type: 'success');
    }

    // ── Exports ────────────────────────────────────────────
    public function exportJournalExcel(): mixed
    {
        abort_unless(auth()->user()->canAccess('accounting.export'), 403);

        return Excel::download(
            new JournalExport($this->journalDateFrom, $this->journalDateTo),
            'journal-'.now()->format('Ymd').'.xlsx'
        );
    }

    public function exportLedgerExcel(): mixed
    {
        abort_unless(auth()->user()->canAccess('accounting.export'), 403);

        return Excel::download(
            new LedgerExport($this->ledgerAccountId),
            'ledger-'.now()->format('Ymd').'.xlsx'
        );
    }

    public function exportPnlPdf(): mixed
    {
        abort_unless(auth()->user()->canAccess('accounting.export'), 403);

        $data = $this->buildPnlData();

        $pdf = Pdf::loadView('reports.accounting.pnl', array_merge($data, [
            'dateFrom' => $this->plDateFrom,
            'dateTo' => $this->plDateTo,
        ]));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'pnl-'.now()->format('Ymd').'.pdf'
        );
    }

    // ── Helpers ────────────────────────────────────────────
    private function buildPnlData(): array
    {
        $revenue = Account::where('type', 'Revenue')
            ->with(['journalEntryLines' => function ($q) {
                $q->whereHas('journalEntry', function ($q2) {
                    $q2->when($this->plDateFrom, fn ($q) => $q->whereDate('date', '>=', $this->plDateFrom))
                        ->when($this->plDateTo, fn ($q) => $q->whereDate('date', '<=', $this->plDateTo));
                });
            }])
            ->get()
            ->map(fn ($a) => [
                'code' => $a->code,
                'name' => $a->name,
                'balance' => bcsub(
                    (string) $a->journalEntryLines->sum('credit'),
                    (string) $a->journalEntryLines->sum('debit'),
                    4
                ),
            ]);

        $expenses = Account::where('type', 'Expense')
            ->with(['journalEntryLines' => function ($q) {
                $q->whereHas('journalEntry', function ($q2) {
                    $q2->when($this->plDateFrom, fn ($q) => $q->whereDate('date', '>=', $this->plDateFrom))
                        ->when($this->plDateTo, fn ($q) => $q->whereDate('date', '<=', $this->plDateTo));
                });
            }])
            ->get()
            ->map(fn ($a) => [
                'code' => $a->code,
                'name' => $a->name,
                'balance' => bcsub(
                    (string) $a->journalEntryLines->sum('debit'),
                    (string) $a->journalEntryLines->sum('credit'),
                    4
                ),
            ]);

        $totalRevenue = $revenue->reduce(fn ($c, $a) => bcadd($c, $a['balance'], 4), '0.0000');
        $totalExpense = $expenses->reduce(fn ($c, $a) => bcadd($c, $a['balance'], 4), '0.0000');
        $netProfit = bcsub($totalRevenue, $totalExpense, 4);

        return compact('revenue', 'expenses', 'totalRevenue', 'totalExpense', 'netProfit');
    }

    public function render()
    {
        $accounts = Account::query()
            ->when($this->accountSearch, fn ($q) => $q->where(function ($q) {
                $q->whereInsensitiveLike('code', "%{$this->accountSearch}%")
                    ->orWhereInsensitiveLike('name', "%{$this->accountSearch}%");
            }))
            ->when($this->accountTypeFilter, fn ($q) => $q->where('type', $this->accountTypeFilter))
            ->orderBy('code')
            ->paginate(15, pageName: 'accountPage');

        $journalEntries = JournalEntry::with(['creator', 'lines'])
            ->when($this->journalSearch, fn ($q) => $q->where(function ($q) {
                $q->whereInsensitiveLike('reference', "%{$this->journalSearch}%")
                    ->orWhereInsensitiveLike('description', "%{$this->journalSearch}%");
            }))
            ->when($this->journalDateFrom, fn ($q) => $q->whereDate('date', '>=', $this->journalDateFrom))
            ->when($this->journalDateTo, fn ($q) => $q->whereDate('date', '<=', $this->journalDateTo))
            ->latest('date')
            ->paginate(15, pageName: 'journalPage');

        $ledgerLines = collect();
        $ledgerAccount = null;
        if ($this->ledgerAccountId) {
            $ledgerAccount = Account::find($this->ledgerAccountId);
            $running = '0.0000';
            $ledgerLines = JournalEntryLine::with(['journalEntry'])
                ->where('account_id', $this->ledgerAccountId)
                ->whereHas('journalEntry')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->when($this->ledgerDateFrom, fn ($q) => $q->whereDate('journal_entries.date', '>=', $this->ledgerDateFrom))
                ->when($this->ledgerDateTo, fn ($q) => $q->whereDate('journal_entries.date', '<=', $this->ledgerDateTo))
                ->orderBy('journal_entries.date')
                ->orderBy('journal_entry_lines.created_at')
                ->select('journal_entry_lines.*')
                ->get()
                ->map(function ($line) use (&$running) {
                    $running = bcadd($running, (string) $line->debit, 4);
                    $running = bcsub($running, (string) $line->credit, 4);

                    return (object) [
                        'date' => $line->journalEntry?->date,
                        'reference' => $line->journalEntry?->reference,
                        'description' => $line->journalEntry?->description,
                        'narration' => $line->narration,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                        'balance' => $running,
                    ];
                });
        }

        $allAccounts = Account::orderBy('code')->get();

        $pnlData = $this->buildPnlData();

        $overview = $this->buildOverview();

        $recoRows = $this->buildReconciliation();

        $chartData = $this->buildMonthlyChartData();

        $selectedEntry = $this->selectedJournalId
            ? JournalEntry::with(['lines.account', 'creator'])->find($this->selectedJournalId)
            : null;

        return view('livewire.accounting.accounting-workspace', [
            'accounts' => $accounts,
            'journalEntries' => $journalEntries,
            'ledgerLines' => $ledgerLines,
            'ledgerAccount' => $ledgerAccount,
            'allAccounts' => $allAccounts,
            'pnlData' => $pnlData,
            'overview' => $overview,
            'recoRows' => $recoRows,
            'chartData' => $chartData,
            'selectedEntry' => $selectedEntry,
        ])->layout('layouts.app', ['title' => 'Accounting Workspace']);
    }

    private function buildOverview(): array
    {
        $types = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'];
        $totals = [];

        foreach ($types as $type) {
            $totals[$type] = Account::where('type', $type)->get()
                ->reduce(fn ($c, $a) => bcadd($c, $a->computedBalance(), 4), '0.0000');
        }

        $totals['netEquity'] = bcsub(
            bcadd($totals['Asset'], '0', 4),
            bcadd($totals['Liability'], '0', 4),
            4
        );

        $totals['recentEntries'] = JournalEntry::with('creator')
            ->latest('date')
            ->limit(5)
            ->get();

        $totals['totalEntries'] = JournalEntry::count();

        return $totals;
    }

    private function buildMonthlyChartData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $data = $months->map(function ($month) {
            $revenue = JournalEntryLine::whereHas('account', fn ($q) => $q->where('type', 'Revenue'))
                ->whereHas('journalEntry', fn ($q) => $q->whereYear('date', $month->year)->whereMonth('date', $month->month))
                ->sum('credit');

            $expenses = JournalEntryLine::whereHas('account', fn ($q) => $q->where('type', 'Expense'))
                ->whereHas('journalEntry', fn ($q) => $q->whereYear('date', $month->year)->whereMonth('date', $month->month))
                ->sum('debit');

            return [
                'month' => $month->format('M Y'),
                'revenue' => round((float) $revenue, 2),
                'expenses' => round((float) $expenses, 2),
            ];
        });

        return [
            'categories' => $data->pluck('month')->values()->toArray(),
            'revenue' => $data->pluck('revenue')->values()->toArray(),
            'expenses' => $data->pluck('expenses')->values()->toArray(),
        ];
    }

    private function buildReconciliation(): Collection
    {
        $internal = Account::orderBy('code')
            ->get()
            ->map(fn ($a) => [
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'internal' => $a->computedBalance(),
                'imported' => null,
            ]);

        $importedMap = collect();
        if (! empty($this->parsedRows)) {
            foreach ($this->parsedRows as $row) {
                $code = $row['Account Code'] ?? $row['code'] ?? null;
                $bal = $row['Balance'] ?? $row['balance'] ?? null;
                if ($code && $bal) {
                    $importedMap[$code] = preg_replace('/[^0-9.\-]/', '', (string) $bal);
                }
            }
        }

        return $internal->map(function ($row) use ($importedMap) {
            $imported = $importedMap->get($row['code']);
            $row['imported'] = $imported;
            $row['variance'] = $imported !== null
                ? bcsub($row['internal'], (string) $imported, 4)
                : null;

            return $row;
        });
    }
}
