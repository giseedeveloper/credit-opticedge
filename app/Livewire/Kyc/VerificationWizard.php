<?php

namespace App\Livewire\Kyc;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use Livewire\Component;

class VerificationWizard extends Component
{
    public int $step = 1;

    public bool $submitted = false;

    public ?string $submittedName = null;

    // ── Step 1: Personal Info ──────────────────────────────────────
    public string $firstName  = '';
    public string $middleName = '';
    public string $lastName   = '';
    public string $gender     = '';
    public string $dob        = '';
    public string $email      = '';

    // ── Step 2: Contact & Location ──────────────────────────────
    public string $phone     = '';
    public string $altPhone  = '';
    public string $address   = '';
    public string $region    = '';
    public string $district  = '';
    public string $branchId  = '';

    // ── Step 3: Financial & Identity ───────────────────────────
    public string $nidaNumber     = '';
    public string $occupation     = '';
    public string $employer       = '';
    public string $monthlyIncome  = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
    }

    /** @return array<string,string> */
    private function step1Rules(): array
    {
        return [
            'firstName' => 'required|string|min:2|max:60',
            'lastName'  => 'required|string|min:2|max:60',
            'gender'    => 'required|in:male,female,other',
        ];
    }

    /** @return array<string,string> */
    private function step2Rules(): array
    {
        return [
            'phone'    => 'required|string|min:9|unique:customers,phone',
            'branchId' => 'required|exists:branches,id',
        ];
    }

    /** @return array<string,string> */
    private function step3Rules(): array
    {
        return [
            'nidaNumber' => 'required|string|size:20|unique:customers,nida_number',
        ];
    }

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validate($this->step1Rules()),
            2 => $this->validate($this->step2Rules()),
            3 => $this->validate($this->step3Rules()),
            default => null,
        };

        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function processApplication(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $this->validate(array_merge(
            $this->step1Rules(),
            $this->step2Rules(),
            $this->step3Rules(),
        ));

        $customer = Customer::create([
            'first_name'     => ucfirst(strtolower(trim($this->firstName))),
            'middle_name'    => $this->middleName ? ucfirst(strtolower(trim($this->middleName))) : null,
            'last_name'      => ucfirst(strtolower(trim($this->lastName))),
            'gender'         => $this->gender,
            'date_of_birth'  => $this->dob ?: null,
            'email'          => $this->email ? strtolower(trim($this->email)) : null,
            'phone'          => trim($this->phone),
            'alt_phone'      => $this->altPhone ? trim($this->altPhone) : null,
            'address'        => $this->address ?: null,
            'region'         => $this->region ?: null,
            'district'       => $this->district ?: null,
            'branch_id'      => $this->branchId ?: null,
            'nida_number'    => strtoupper(trim($this->nidaNumber)),
            'occupation'     => $this->occupation ?: null,
            'employer'       => $this->employer ?: null,
            'monthly_income' => $this->monthlyIncome ?: null,
            'kyc_status'     => 'pending',
            'registered_by'  => auth()->id(),
        ]);

        Verification::create([
            'customer_id' => $customer->id,
            'type'        => 'kyc',
            'status'      => 'pending',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->log("KYC application submitted for {$customer->full_name} — NIDA: {$customer->nida_number}");

        $this->submittedName = $customer->full_name;
        $this->submitted     = true;
    }

    public function startNew(): void
    {
        $this->reset();
        $this->step = 1;
    }

    public function render()
    {
        $recentProfiles = Customer::with('latestVerification')
            ->latest()
            ->take(6)
            ->get();

        $branches = Branch::orderBy('name')->get();

        return view('livewire.kyc.verification-wizard', compact('recentProfiles', 'branches'))
            ->layout('layouts.app', ['title' => 'KYC Wizard']);
    }
}
