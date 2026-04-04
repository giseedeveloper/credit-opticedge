<?php

namespace App\Livewire\Kyc;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class VerificationWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public bool $submitted = false;

    public ?string $submittedName = null;

    /** @var array<string, mixed>|null */
    public ?array $autoCheckResult = null;

    // ── Step 1: Device ────────────────────────────────────────────
    public string $deviceSpecs = '';

    public string $imeiNumber = '';

    public string $imei2 = '';

    public string $serialNumber = '';

    public string $cashPrice = '';

    public string $depositAmount = '';

    public string $preferredRepayment = '';

    /** @var TemporaryUploadedFile|null */
    public $imeiPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $deviceBoxPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $devicePhoto = null;

    // ── Step 2: Customer Identity ─────────────────────────────────
    public string $firstName = '';

    public string $middleName = '';

    public string $lastName = '';

    public string $gender = '';

    public string $dob = '';

    public string $nidaNumber = '';

    public string $idType = '';

    /** @var TemporaryUploadedFile|null */
    public $idFrontPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $idBackPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $headshotPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $clientFoPhoto = null;

    // ── Step 3: Contact & Location ────────────────────────────────
    public string $phone = '';

    public string $altPhone = '';

    public string $email = '';

    public string $branchId = '';

    public string $address = '';

    public string $landmark = '';

    public string $region = '';

    public string $district = '';

    public string $latitude = '';

    public string $longitude = '';

    // ── Step 4: Income & Work ─────────────────────────────────────
    public string $occupation = '';

    public string $employer = '';

    public string $workLocation = '';

    public string $monthlyIncome = '';

    public string $monthlyExpenses = '';

    public string $incomePaymentCycle = '';

    public string $durationAtWork = '';

    /** @var TemporaryUploadedFile|null */
    public $businessPhoto = null;

    // ── Step 5: Next of Kin ───────────────────────────────────────
    public string $nokName = '';

    public string $nokPhone = '';

    public string $nokRelationship = '';

    public string $nok2Name = '';

    public string $nok2Phone = '';

    public string $nok2Relationship = '';

    // ── Step 6: Consent ───────────────────────────────────────────
    public bool $termsAccepted = false;

    public bool $dataConsentAccepted = false;

    public bool $callConsentAccepted = false;

    // ── Step 7: Submit ────────────────────────────────────────────
    public string $foNotes = '';

    public string $applicationSource = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
    }

    /** @return array<string,mixed> */
    private function step1Rules(): array
    {
        return [
            'deviceSpecs' => ['required', 'string', 'min:3', 'max:200'],
            'imeiNumber' => ['required', 'string', 'digits:15'],
            'imei2' => ['nullable', 'string', 'digits:15'],
            'serialNumber' => ['nullable', 'string', 'max:60'],
            'cashPrice' => ['required', 'numeric', 'min:1'],
            'depositAmount' => ['required', 'numeric', 'min:0'],
            'preferredRepayment' => ['required', 'in:weekly,biweekly,monthly'],
            'imeiPhoto' => ['nullable', 'image', 'max:5120'],
            'deviceBoxPhoto' => ['nullable', 'image', 'max:5120'],
            'devicePhoto' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /** @return array<string,mixed> */
    private function step2Rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'min:2', 'max:60'],
            'lastName' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
            'nidaNumber' => ['required', 'string', 'size:20', 'unique:customers,nida_number'],
            'idType' => ['required', 'in:nida,passport,driving_license,voter_card'],
            'idFrontPhoto' => ['required', 'image', 'max:5120'],
            'idBackPhoto' => ['required', 'image', 'max:5120'],
            'headshotPhoto' => ['required', 'image', 'max:5120'],
            'clientFoPhoto' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /** @return array<string,mixed> */
    private function step3Rules(): array
    {
        return [
            'phone' => ['required', 'string', 'min:9', 'unique:customers,phone'],
            'branchId' => ['required', 'exists:branches,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /** @return array<string,mixed> */
    private function step4Rules(): array
    {
        return [
            'monthlyIncome' => ['required', 'numeric', 'min:0'],
            'businessPhoto' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /** @return array<string,mixed> */
    private function step5Rules(): array
    {
        return [
            'nokName' => ['required', 'string', 'min:2', 'max:100'],
            'nokPhone' => ['required', 'string', 'min:9'],
            'nokRelationship' => ['required', 'string', 'max:60'],
        ];
    }

    /** @return array<string,mixed> */
    private function step6Rules(): array
    {
        return [
            'termsAccepted' => ['accepted'],
            'dataConsentAccepted' => ['accepted'],
            'callConsentAccepted' => ['accepted'],
        ];
    }

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validate($this->step1Rules()),
            2 => $this->validate($this->step2Rules()),
            3 => $this->validate($this->step3Rules()),
            4 => $this->validate($this->step4Rules()),
            5 => $this->validate($this->step5Rules()),
            6 => $this->validate($this->step6Rules()),
            default => null,
        };

        if ($this->step < 7) {
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

    /** @return array<string,mixed> */
    private function submitRules(): array
    {
        return [
            // Step 1
            'deviceSpecs' => ['required', 'string', 'min:3', 'max:200'],
            'imeiNumber' => ['required', 'string', 'digits:15'],
            'cashPrice' => ['required', 'numeric', 'min:1'],
            'depositAmount' => ['required', 'numeric', 'min:0'],
            'preferredRepayment' => ['required', 'in:weekly,biweekly,monthly'],
            // Step 2
            'firstName' => ['required', 'string', 'min:2', 'max:60'],
            'lastName' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
            'nidaNumber' => ['required', 'string', 'size:20', 'unique:customers,nida_number'],
            'idType' => ['required', 'in:nida,passport,driving_license,voter_card'],
            // Step 3
            'phone' => ['required', 'string', 'min:9', 'unique:customers,phone'],
            'branchId' => ['required', 'exists:branches,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Step 4
            'monthlyIncome' => ['required', 'numeric', 'min:0'],
            // Step 5
            'nokName' => ['required', 'string', 'min:2', 'max:100'],
            'nokPhone' => ['required', 'string', 'min:9'],
            'nokRelationship' => ['required', 'string', 'max:60'],
            // Step 6
            'termsAccepted' => ['accepted'],
            'dataConsentAccepted' => ['accepted'],
            'callConsentAccepted' => ['accepted'],
        ];
    }

    public function processApplication(ApplicationAutoCheckService $checker): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $this->validate($this->submitRules());

        $customer = Customer::create([
            'registered_by' => auth()->id(),
            'branch_id' => $this->branchId ?: null,
            // Step 1 – Device
            'device_specs' => trim($this->deviceSpecs),
            'imei_number' => strtoupper(trim($this->imeiNumber)),
            'imei_2' => $this->imei2 ? strtoupper(trim($this->imei2)) : null,
            'serial_number' => $this->serialNumber ? strtoupper(trim($this->serialNumber)) : null,
            'cash_price' => $this->cashPrice,
            'deposit_amount' => $this->depositAmount,
            'preferred_repayment' => $this->preferredRepayment,
            'imei_photo_path' => $this->storePhoto($this->imeiPhoto, 'imei'),
            'device_box_photo_path' => $this->storePhoto($this->deviceBoxPhoto, 'device_box'),
            'device_photo_path' => $this->storePhoto($this->devicePhoto, 'device'),
            // Step 2 – Identity
            'first_name' => ucfirst(strtolower(trim($this->firstName))),
            'middle_name' => $this->middleName ? ucfirst(strtolower(trim($this->middleName))) : null,
            'last_name' => ucfirst(strtolower(trim($this->lastName))),
            'gender' => $this->gender,
            'date_of_birth' => $this->dob ?: null,
            'nida_number' => strtoupper(trim($this->nidaNumber)),
            'id_type' => $this->idType,
            'id_front_photo_path' => $this->storePhoto($this->idFrontPhoto, 'id_front'),
            'id_back_photo_path' => $this->storePhoto($this->idBackPhoto, 'id_back'),
            'headshot_photo_path' => $this->storePhoto($this->headshotPhoto, 'headshot'),
            'client_fo_photo_path' => $this->storePhoto($this->clientFoPhoto, 'client_fo'),
            // Step 3 – Contact
            'phone' => trim($this->phone),
            'alt_phone' => $this->altPhone ? trim($this->altPhone) : null,
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'address' => $this->address ?: null,
            'landmark' => $this->landmark ?: null,
            'region' => $this->region ?: null,
            'district' => $this->district ?: null,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            // Step 4 – Work/Income
            'occupation' => $this->occupation ?: null,
            'employer' => $this->employer ?: null,
            'work_location' => $this->workLocation ?: null,
            'monthly_income' => $this->monthlyIncome,
            'monthly_expenses' => $this->monthlyExpenses ?: null,
            'income_payment_cycle' => $this->incomePaymentCycle ?: null,
            'duration_at_work' => $this->durationAtWork ?: null,
            'business_photo_path' => $this->storePhoto($this->businessPhoto, 'business'),
            // Step 5 – NOK
            'nok_name' => trim($this->nokName),
            'nok_phone' => trim($this->nokPhone),
            'nok_relationship' => $this->nokRelationship,
            'nok2_name' => $this->nok2Name ? trim($this->nok2Name) : null,
            'nok2_phone' => $this->nok2Phone ? trim($this->nok2Phone) : null,
            'nok2_relationship' => $this->nok2Relationship ?: null,
            // Step 6 – Consent
            'terms_accepted' => $this->termsAccepted,
            'data_consent_accepted' => $this->dataConsentAccepted,
            'call_consent_accepted' => $this->callConsentAccepted,
            'consent_timestamp' => now(),
            // Step 7 – FO metadata
            'fo_notes' => $this->foNotes ?: null,
            'application_source' => $this->applicationSource ?: null,
            'kyc_status' => 'pending',
            'kyc_stage' => 1,
        ]);

        $verification = Verification::create([
            'customer_id' => $customer->id,
            'fo_id' => auth()->id(),
            'type' => 'kyc',
            'status' => 'pending',
            'stage' => 1,
            'stage1_status' => 'pending',
        ]);

        // Run automated checks
        $result = $checker->run($customer, $verification);
        $this->autoCheckResult = $result;

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['auto_check_status' => $result['status']])
            ->log("KYC application submitted for {$customer->full_name} — auto-check: {$result['status']}");

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
