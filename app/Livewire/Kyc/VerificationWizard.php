<?php

namespace App\Livewire\Kyc;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class VerificationWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public bool $submitted = false;

    public ?string $submittedName = null;

    // ── Step 1: Device Verification ──────────────────────────────
    public string $imeiNumber = '';

    public string $deviceSpecs = '';

    /** @var TemporaryUploadedFile|null */
    public $imeiPhoto = null;

    // ── Step 2: Personal Info ─────────────────────────────────────
    public string $firstName = '';

    public string $middleName = '';

    public string $lastName = '';

    public string $gender = '';

    public string $dob = '';

    public string $email = '';

    // ── Step 3: Contact & Location ────────────────────────────────
    public string $phone = '';

    public string $altPhone = '';

    public string $address = '';

    public string $region = '';

    public string $district = '';

    public string $branchId = '';

    // ── Step 4: Identity, Financial & NOK ────────────────────────
    public string $nidaNumber = '';

    public string $occupation = '';

    public string $employer = '';

    public string $monthlyIncome = '';

    public string $monthlyExpenses = '';

    public string $nokName = '';

    public string $nokPhone = '';

    public string $nokRelationship = '';

    /** @var TemporaryUploadedFile|null */
    public $idFrontPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $idBackPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $headshotPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $clientFoPhoto = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
    }

    /** @return array<string,mixed> */
    private function step1Rules(): array
    {
        return [
            'imeiNumber' => ['required', 'string', 'digits:15', 'regex:/^\d{15}$/'],
            'deviceSpecs' => ['required', 'string', 'min:3', 'max:200'],
            'imeiPhoto' => ['nullable', 'image', 'max:4096'],
        ];
    }

    /** @return array<string,mixed> */
    private function step2Rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'min:2', 'max:60'],
            'lastName' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
        ];
    }

    /** @return array<string,mixed> */
    private function step3Rules(): array
    {
        return [
            'phone' => ['required', 'string', 'min:9', 'unique:customers,phone'],
            'branchId' => ['required', 'exists:branches,id'],
        ];
    }

    /** @return array<string,mixed> */
    private function step4Rules(): array
    {
        return [
            'nidaNumber' => ['required', 'string', 'size:20', 'unique:customers,nida_number'],
            'monthlyIncome' => ['required', 'numeric', 'min:0'],
            'nokName' => ['required', 'string', 'min:2', 'max:100'],
            'nokPhone' => ['required', 'string', 'min:9'],
            'nokRelationship' => ['required', 'string', 'max:60'],
            'idFrontPhoto' => ['nullable', 'image', 'max:4096'],
            'idBackPhoto' => ['nullable', 'image', 'max:4096'],
            'headshotPhoto' => ['nullable', 'image', 'max:4096'],
            'clientFoPhoto' => ['nullable', 'image', 'max:4096'],
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

    private function storePhoto(mixed $file, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store("kyc/{$directory}", 'public');
    }

    public function processApplication(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $this->validate(array_merge(
            $this->step1Rules(),
            $this->step2Rules(),
            $this->step3Rules(),
            $this->step4Rules(),
        ));

        $customer = Customer::create([
            'registered_by' => auth()->id(),
            'branch_id' => $this->branchId ?: null,
            // Stage 1
            'imei_number' => strtoupper(trim($this->imeiNumber)),
            'device_specs' => trim($this->deviceSpecs),
            'imei_photo_path' => $this->storePhoto($this->imeiPhoto, 'imei'),
            // Stage 2
            'first_name' => ucfirst(strtolower(trim($this->firstName))),
            'middle_name' => $this->middleName ? ucfirst(strtolower(trim($this->middleName))) : null,
            'last_name' => ucfirst(strtolower(trim($this->lastName))),
            'gender' => $this->gender,
            'date_of_birth' => $this->dob ?: null,
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            // Stage 3
            'phone' => trim($this->phone),
            'alt_phone' => $this->altPhone ? trim($this->altPhone) : null,
            'address' => $this->address ?: null,
            'region' => $this->region ?: null,
            'district' => $this->district ?: null,
            // Stage 4
            'nida_number' => strtoupper(trim($this->nidaNumber)),
            'occupation' => $this->occupation ?: null,
            'employer' => $this->employer ?: null,
            'monthly_income' => $this->monthlyIncome ?: null,
            'monthly_expenses' => $this->monthlyExpenses ?: null,
            'id_front_photo_path' => $this->storePhoto($this->idFrontPhoto, 'id_front'),
            'id_back_photo_path' => $this->storePhoto($this->idBackPhoto, 'id_back'),
            'headshot_photo_path' => $this->storePhoto($this->headshotPhoto, 'headshot'),
            'client_fo_photo_path' => $this->storePhoto($this->clientFoPhoto, 'client_fo'),
            'nok_name' => trim($this->nokName),
            'nok_phone' => trim($this->nokPhone),
            'nok_relationship' => trim($this->nokRelationship),
            'kyc_status' => 'pending',
            'kyc_stage' => 1,
        ]);

        Verification::create([
            'customer_id' => $customer->id,
            'type' => 'kyc',
            'status' => 'pending',
            'stage' => 1,
            'stage1_status' => 'pending',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->log("KYC application submitted for {$customer->full_name} — IMEI: {$customer->imei_number}");

        $this->submittedName = $customer->full_name;
        $this->submitted = true;
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
