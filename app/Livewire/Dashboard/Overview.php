<?php

namespace App\Livewire\Dashboard;

use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\Transaction;
use Livewire\Component;

class Overview extends Component
{
    public function render()
    {
        $stats = [
            'active_loans' => Loan::where('status', 'active')->count(),
            'overdue_loans' => Loan::where('status', 'overdue')->count(),
            'total_customers' => Customer::count(),
            'hq_stock' => InventoryUnit::where('status', 'hq_stock')->count(),
            'vendor_stock' => InventoryUnit::where('status', 'vendor_stock')->count(),
            'sold_units' => InventoryUnit::where('status', 'sold')->count(),
            'pending_kyc' => Customer::whereDoesntHave('verifications')->count(),
            'daily_collections' => Transaction::where('entry_type', 'credit')
                ->whereDate('transacted_at', today())
                ->sum('amount'),
            'monthly_collections' => Transaction::where('entry_type', 'credit')
                ->whereMonth('transacted_at', now()->month)
                ->sum('amount'),
            'total_portfolio' => Loan::where('status', 'active')->sum('outstanding_balance'),
        ];

        $recentLoans = Loan::with(['customer', 'inventoryUnit.phoneModel'])
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.dashboard.overview', compact('stats', 'recentLoans'))
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
