<?php

namespace App\Livewire\Credit;

use App\Services\LoanCalculatorService;
use Carbon\Carbon;
use Livewire\Component;

class LoanCalculator extends Component
{
    public float $principal = 500000;

    public float $interestRate = 24;

    public int $durationWeeks = 12;

    public string $interestType = 'flat';

    public string $repaymentFrequency = 'weekly';

    public float $deposit = 0;

    public string $startDate = '';

    public bool $showSchedule = false;

    public ?array $result = null;

    public ?array $comparison = null;

    public array $schedule = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('calculator.view'), 403);
        $this->startDate = now()->toDateString();
    }

    public function calculate(): void
    {
        $this->validate([
            'principal' => 'required|numeric|min:10000',
            'interestRate' => 'required|numeric|min:0|max:100',
            'durationWeeks' => 'required|integer|min:1|max:260',
            'interestType' => 'required|in:flat,reducing_balance',
            'repaymentFrequency' => 'required|in:weekly,biweekly,monthly',
            'deposit' => 'nullable|numeric|min:0',
            'startDate' => 'nullable|date',
        ]);

        $service = app(LoanCalculatorService::class);

        $financedPrincipal = max(0, $this->principal - $this->deposit);

        $computed = $this->interestType === 'flat'
            ? $service->computeFlat($financedPrincipal, $this->interestRate, $this->durationWeeks)
            : $service->computeReducingBalance($financedPrincipal, $this->interestRate, $this->durationWeeks);

        $installments = $this->repaymentFrequency === 'monthly'
            ? (int) ceil($this->durationWeeks / 4.33)
            : ($this->repaymentFrequency === 'biweekly'
                ? (int) ceil($this->durationWeeks / 2)
                : $this->durationWeeks);

        $installmentPay = round($computed['total_payable'] / $installments, 2);
        $interestRatio = $computed['total_payable'] > 0
            ? round(($computed['total_interest'] / $computed['total_payable']) * 100, 1)
            : 0;

        $this->result = array_merge($computed, [
            'installments' => $installments,
            'frequency_label' => ucfirst($this->repaymentFrequency),
            'installment_per_pay' => $installmentPay,
            'financed_principal' => $financedPrincipal,
            'interest_ratio' => $interestRatio,
        ]);

        // Comparison: the opposite method
        $altType = $this->interestType === 'flat' ? 'reducing_balance' : 'flat';
        $altComputed = $altType === 'flat'
            ? $service->computeFlat($financedPrincipal, $this->interestRate, $this->durationWeeks)
            : $service->computeReducingBalance($financedPrincipal, $this->interestRate, $this->durationWeeks);

        $this->comparison = array_merge($altComputed, [
            'type' => $altType,
            'installments' => $installments,
            'installment_per_pay' => round($altComputed['total_payable'] / $installments, 2),
            'interest_ratio' => $altComputed['total_payable'] > 0
                ? round(($altComputed['total_interest'] / $altComputed['total_payable']) * 100, 1) : 0,
        ]);

        // Amortization schedule
        $this->schedule = $this->buildSchedule($financedPrincipal, $installmentPay, $installments, $computed['total_interest']);
        $this->showSchedule = false;
    }

    public function toggleSchedule(): void
    {
        $this->showSchedule = ! $this->showSchedule;
    }

    private function buildSchedule(float $principal, float $installmentAmt, int $installments, float $totalInterest): array
    {
        $schedule = [];
        $balance = $principal;
        $startDate = $this->startDate ? Carbon::parse($this->startDate) : now();

        $periodRate = match ($this->repaymentFrequency) {
            'monthly' => ($this->interestRate / 100) / 12,
            'biweekly' => ($this->interestRate / 100) / 26,
            default => ($this->interestRate / 100) / 52,
        };

        $maxDisplay = min($installments, 60);

        for ($i = 1; $i <= $maxDisplay; $i++) {
            $dueDate = match ($this->repaymentFrequency) {
                'monthly' => $startDate->copy()->addMonths($i),
                'biweekly' => $startDate->copy()->addWeeks($i * 2),
                default => $startDate->copy()->addWeeks($i),
            };

            if ($this->interestType === 'flat') {
                $interest = round($totalInterest / $installments, 2);
                $principalComp = round($principal / $installments, 2);
            } else {
                $interest = round($balance * $periodRate, 2);
                $principalComp = round($installmentAmt - $interest, 2);
            }

            $balance = max(0, round($balance - $principalComp, 2));

            $schedule[] = [
                'no' => $i,
                'due_date' => $dueDate->format('d M Y'),
                'principal' => $principalComp,
                'interest' => $interest,
                'amount' => $installmentAmt,
                'balance' => $balance,
            ];
        }

        return $schedule;
    }

    public function render()
    {
        return view('livewire.credit.loan-calculator')
            ->layout('layouts.app', ['title' => 'Loan Calculator']);
    }
}
