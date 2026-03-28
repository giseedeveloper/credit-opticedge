<?php

namespace App\Livewire\Notifications;

use App\Models\Loan;
use Livewire\Component;

class AlertBell extends Component
{
    public bool $showDropdown = false;

    public function getAlertsProperty(): array
    {
        $overdue = Loan::with('customer')
            ->whereIn('status', ['overdue', 'defaulted'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn ($loan) => [
                'type' => $loan->status === 'defaulted' ? 'danger' : 'warning',
                'icon' => $loan->status === 'defaulted' ? 'exclamation-circle' : 'clock',
                'message' => ($loan->customer?->first_name ?? 'Customer').' — '.$loan->loan_number,
                'detail' => 'Balance: TZS '.number_format($loan->remaining_balance),
            ])
            ->toArray();

        $stockLow = Loan::whereIn('status', ['active'])
            ->where('due_date', '<', now()->addDays(7))
            ->where('due_date', '>', now())
            ->count();

        $extras = [];
        if ($stockLow > 0) {
            $extras[] = [
                'type' => 'info',
                'icon' => 'calendar-days',
                'message' => "{$stockLow} loans due within 7 days",
                'detail' => 'Review repayment schedules',
            ];
        }

        return array_merge($overdue, $extras);
    }

    public function getCountProperty(): int
    {
        return Loan::whereIn('status', ['overdue', 'defaulted'])->count();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.notifications.alert-bell');
    }
}
