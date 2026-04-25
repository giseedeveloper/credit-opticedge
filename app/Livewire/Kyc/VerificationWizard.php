<?php

namespace App\Livewire\Kyc;

use App\Jobs\ProcessFaceMatchJob;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\PhoneModel;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use App\Services\CustomerLoanProvisioningService;
use App\Services\DeviceIdentifierScanService;
use App\Services\ExternalDeviceCatalogService;
use App\Services\IMEITrackingService;
use App\Services\KycAccessoryOfferService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use App\Services\SelcomCheckoutService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class VerificationWizard extends Component
{
    use WithFileUploads;

    /**
     * 7-step wizard (FO capture flow):
     * 1 = Device
     * 2 = Identity
     * 3 = Contact
     * 4 = Income
     * 5 = Next of Kin
     * 6 = Consent
     * 7 = Submit
     */
    public int $step = 1;

    public bool $submitted = false;

    public ?string $submittedName = null;

    /** @var array<string, mixed>|null */
    public ?array $autoCheckResult = null;

    public string $draftReference = '';

    public string $draftCode = '';

    // ── Step 1: Device ────────────────────────────────────────────
    public string $brandId = '';

    public string $phoneModelId = '';

    public string $inventoryUnitId = '';

    public string $inventorySearch = '';

    public string $deviceSpecs = '';

    public string $imeiNumber = '';

    public string $imei2 = '';

    public string $cashPrice = '';

    public string $depositAmount = '';

    public string $preferredRepayment = '';

    public string $loanInterestRate = '';

    public string $loanInterestType = 'flat';

    public string $loanDurationWeeks = '';

    public string $loanGracePeriodDays = '';

    /** @var array<int, array<string, mixed>> */
    public array $deviceAccessories = [];

    public bool $includeScreenProtector = false;

    public bool $includePhoneCover = false;

    public string $storeOfferNotes = '';

    /** @var TemporaryUploadedFile|null */
    public $imeiPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $deviceBoxPhoto = null;

    /** @var TemporaryUploadedFile|null */
    public $devicePhoto = null;

    /** @var array<string, mixed> */
    public array $deviceScan = [];

    public ?string $scanFeedbackMessage = null;

    public string $scanFeedbackTone = 'slate';

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

    public string $phoneCountry = 'TZ';

    public string $altPhone = '';

    public string $altPhoneCountry = 'TZ';

    public string $email = '';

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

    public bool $isPep = false;

    /** @var TemporaryUploadedFile|null */
    public $businessPhoto = null;

    // ── Step 5: Next of Kin ───────────────────────────────────────
    public string $nokName = '';

    public string $nokPhone = '';

    public string $nokPhoneCountry = 'TZ';

    public string $nokRelationship = '';

    public string $nok2Name = '';

    public string $nok2Phone = '';

    public string $nok2PhoneCountry = 'TZ';

    public string $nok2Relationship = '';

    /** @var array<string, mixed> */
    public array $phoneMetadata = [];

    // ── Step 6: Consent ───────────────────────────────────────────
    public bool $termsAccepted = false;

    public bool $dataConsentAccepted = false;

    public bool $callConsentAccepted = false;

    // ── Payment, Agreement & Signatures ───────────────────────────
    public string $paymentPhone = '';

    public string $paymentStatus = 'pending';

    public string $paymentMessage = '';

    public ?string $paymentRecordId = null;

    public string $linkPaymentReference = '';

    public string $agreementDecision = '';

    public string $customerSignatureData = '';

    public string $foSignatureData = '';

    /** @var TemporaryUploadedFile|null */
    public $assetHandoverList = null;

    /** @var TemporaryUploadedFile|null */
    public $etrReceiptPhoto = null;

    public string $assetHandoverNotes = '';

    // ── Step 7: Submit ────────────────────────────────────────────
    public string $foNotes = '';

    public string $applicationSource = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $this->draftReference = (string) Str::uuid();
        $this->draftCode = $this->generateDraftCode();
        $this->preferredRepayment = 'weekly';
        $this->seedLoanTermsFromDefaults(overwrite: true);
    }

    /** @return array<string,mixed> */
    private function step1Rules(): array
    {
        return [
            'brandId' => ['required', 'exists:brands,id'],
            'phoneModelId' => ['required', 'exists:phone_models,id'],
            'imeiNumber' => ['required', 'string', 'digits:15'],
            'imei2' => ['nullable', 'string', 'digits:15'],
            'cashPrice' => ['required', 'numeric', 'min:1'],
            'depositAmount' => ['required', 'numeric', 'min:0'],
            'preferredRepayment' => ['required', 'in:weekly,biweekly,monthly'],
            'imeiPhoto' => ['nullable', 'image', 'max:5120'],
            'deviceBoxPhoto' => ['nullable', 'image', 'max:5120'],
            'devicePhoto' => ['nullable', 'image', 'max:5120'],
            'deviceScan' => ['nullable', 'array'],
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
            'phone' => ['required', 'string', 'min:7', 'max:20', 'unique:customers,phone'],
            'phoneCountry' => ['required', 'string', 'size:2'],
            'altPhone' => ['nullable', 'string', 'min:7', 'max:20', 'different:phone'],
            'altPhoneCountry' => ['required', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:120'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /** @return array<string,mixed> */
    private function step4Rules(): array
    {
        return [
            'occupation' => ['required', 'string', 'max:100'],
            'isPep' => ['boolean'],
            'monthlyIncome' => ['required', 'numeric', 'min:0'],
            'durationAtWork' => ['nullable', 'string', 'max:60'],
            'businessPhoto' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /** @return array<string,mixed> */
    private function step5Rules(): array
    {
        return [
            'nokName' => ['required', 'string', 'min:2', 'max:100'],
            'nokPhone' => ['required', 'string', 'min:7', 'max:20'],
            'nokPhoneCountry' => ['required', 'string', 'size:2'],
            'nokRelationship' => ['required', 'in:spouse,parent,sibling,friend,relative,other'],
            'nok2Phone' => ['nullable', 'string', 'min:7', 'max:20', 'different:nokPhone'],
            'nok2PhoneCountry' => ['required', 'string', 'size:2'],
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

    private function validateStepTwo(): void
    {
        $this->validate([
            ...$this->step2Rules(),
            'middleName' => ['nullable', 'string', 'max:60'],
            'dob' => ['nullable', 'date', 'before:today'],
        ]);
    }

    private function validateStepThree(): void
    {
        $this->normalizeContactPhones();
        $this->validate($this->step3Rules());
    }

    private function validateStepFour(): void
    {
        if ($this->incomePaymentCycle === '') {
            $this->incomePaymentCycle = 'monthly';
        }
        $this->validate($this->step4Rules());
    }

    private function validateStepFive(): void
    {
        $this->normalizeNextOfKinPhones();
        $this->validate([
            ...$this->step5Rules(),
            'nok2Name' => ['nullable', 'string', 'max:100'],
            'nok2Relationship' => ['nullable', 'in:spouse,parent,sibling,friend,relative,other'],
        ]);
    }

    private function validateStepSix(): void
    {
        $this->validate($this->step6Rules());
    }

    public function addAccessoryPreset(string $code): void
    {
        $offerService = app(KycAccessoryOfferService::class);
        $preset = $offerService->presetItem($code);

        $existingIndex = collect($this->deviceAccessories)->search(
            fn (array $item): bool => ($item['code'] ?? '') === $preset['code']
        );

        if ($existingIndex !== false) {
            $this->deviceAccessories[$existingIndex]['quantity'] = ((int) ($this->deviceAccessories[$existingIndex]['quantity'] ?? 1)) + 1;

            return;
        }

        $this->deviceAccessories[] = $preset;
    }

    public function updatedIncludeScreenProtector(): void
    {
        $this->syncAccessoryToggle('screen_protector', $this->includeScreenProtector);
    }

    public function updatedIncludePhoneCover(): void
    {
        $this->syncAccessoryToggle('phone_cover', $this->includePhoneCover);
    }

    private function syncAccessoryToggle(string $code, bool $enabled): void
    {
        $normalized = app(KycAccessoryOfferService::class)->normalize($this->deviceAccessories);
        $index = collect($normalized)->search(fn (array $item): bool => ($item['code'] ?? '') === $code);

        if ($enabled) {
            if ($index === false) {
                $normalized[] = app(KycAccessoryOfferService::class)->presetItem($code);
            }
        } else {
            if ($index !== false) {
                unset($normalized[$index]);
                $normalized = array_values($normalized);
            }
        }

        $this->deviceAccessories = $normalized;
    }

    public function addCustomAccessory(): void
    {
        $this->deviceAccessories[] = app(KycAccessoryOfferService::class)->blankItem();
    }

    public function removeAccessoryItem(int $index): void
    {
        unset($this->deviceAccessories[$index]);
        $this->deviceAccessories = array_values($this->deviceAccessories);
    }

    public function updatedBrandId(): void
    {
        $this->reset([
            'phoneModelId',
            'inventoryUnitId',
            'inventorySearch',
            'deviceSpecs',
            'cashPrice',
            'deviceScan',
            'scanFeedbackMessage',
        ]);
        $this->scanFeedbackTone = 'slate';

        $brand = Brand::query()
            ->whereKey($this->brandId !== '' ? $this->brandId : null)
            ->where('is_active', true)
            ->first();

        if (! $brand) {
            return;
        }

        $synced = app(ExternalDeviceCatalogService::class)->syncModelsForBrand($brand);

        if ($synced > 0) {
            $this->dispatch('toast', message: "Models updated for {$brand->name}.", type: 'success');
        }
    }

    public function updatedPhoneModelId(): void
    {
        $this->reset([
            'inventoryUnitId',
            'inventorySearch',
            'deviceScan',
            'scanFeedbackMessage',
        ]);
        $this->scanFeedbackTone = 'slate';

        if ($this->phoneModelId === '') {
            $this->deviceSpecs = '';
            $this->cashPrice = '';

            return;
        }

        $phoneModel = PhoneModel::query()
            ->with('brand')
            ->whereKey($this->phoneModelId)
            ->where('is_active', true)
            ->first();

        if (! $phoneModel) {
            $this->addError('phoneModelId', 'Selected model is not available.');

            return;
        }

        $this->brandId = (string) $phoneModel->brand_id;
        $this->deviceSpecs = app(KycDeviceCatalogService::class)->buildDeviceSpecs($phoneModel);
        $this->cashPrice = (string) ((float) $phoneModel->retail_price);
        $this->seedLoanTermsFromDefaults();
    }

    public function updatedInventoryUnitId(KycDeviceCatalogService $catalog): void
    {
        if ($this->inventoryUnitId === '') {
            $this->deviceScan = [];
            $this->scanFeedbackMessage = null;
            $this->scanFeedbackTone = 'slate';

            $phoneModel = $catalog->accessibleModel(auth()->user(), $this->phoneModelId);

            if ($phoneModel) {
                $this->deviceSpecs = $catalog->buildDeviceSpecs($phoneModel);
                $this->cashPrice = (string) ((float) $phoneModel->retail_price);
            }

            $this->seedLoanTermsFromDefaults();

            return;
        }

        $unit = $catalog->accessibleUnit(auth()->user(), $this->inventoryUnitId);

        if (! $unit) {
            if ($this->inventoryUnitId !== '') {
                $this->addError('inventoryUnitId', 'Selected stock unit is not available.');
            }

            return;
        }

        $phoneModel = $unit->phoneModel;

        if (! $phoneModel) {
            $this->addError('inventoryUnitId', 'Selected stock unit has no linked phone model.');

            return;
        }

        $this->phoneModelId = (string) $phoneModel->id;
        $this->brandId = (string) $phoneModel->brand_id;
        $this->deviceSpecs = $catalog->buildDeviceSpecs($phoneModel);
        $this->cashPrice = (string) ((float) $phoneModel->retail_price);
        $this->imeiNumber = $unit->imei_1;
        $this->imei2 = $unit->imei_2 ?? '';
        $this->scanFeedbackTone = 'sky';
        $this->scanFeedbackMessage = 'Stock unit linked. IMEI has been loaded automatically from inventory.';
        $this->seedLoanTermsFromDefaults();
    }

    public function updatedPreferredRepayment(): void
    {
        $this->seedLoanTermsFromDefaults();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyDetectedIdentifiers(
        array $payload,
        DeviceIdentifierScanService $scanService,
        KycDeviceCatalogService $catalog
    ): void {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $scan = $scanService->parseClientPayload($payload);
        $this->deviceScan = $scan;

        $detectedImei = $scan['selected_imei'] ?? null;
        $detectedImei2 = $scan['selected_imei_2'] ?? null;
        $detectedSerial = $scan['selected_serial'] ?? null;
        $detectedEmail = is_string($payload['detected_email'] ?? null) ? trim((string) $payload['detected_email']) : null;
        $detectedModelText = is_string($payload['detected_model_text'] ?? null) ? trim((string) $payload['detected_model_text']) : null;
        $detectedModelCode = is_string($payload['detected_model_code'] ?? null) ? trim((string) $payload['detected_model_code']) : null;
        $detectedColor = is_string($payload['detected_color'] ?? null) ? trim((string) $payload['detected_color']) : null;
        $detectedRam = is_string($payload['detected_ram'] ?? null) ? trim((string) $payload['detected_ram']) : null;
        $detectedStorage = is_string($payload['detected_storage'] ?? null) ? trim((string) $payload['detected_storage']) : null;
        $linkedUnit = $catalog->accessibleUnit(auth()->user(), $this->inventoryUnitId);

        if (! $detectedImei && ! $detectedSerial) {
            $this->scanFeedbackTone = 'amber';
            $this->scanFeedbackMessage = 'Uploaded image was received, but no clear IMEI or serial number was detected. You can still enter the IMEI manually.';

            return;
        }

        // Helpful UX: if user hasn't started searching inventory yet, auto-fill the search box
        // with the best identifier so they can select the right stock unit faster.
        if ($this->inventorySearch === '') {
            $this->inventorySearch = (string) ($detectedImei ?: $detectedSerial ?: '');
        }

        // If a device box scan detected an email and the user hasn't entered one, prefill it.
        if ($this->email === '' && $detectedEmail) {
            $this->email = strtolower($detectedEmail);
        }

        // Keep a small hint in scan metadata (no DB write here; it will persist on submit).
        if ($detectedModelText) {
            $this->deviceScan['detected_model_text'] = $detectedModelText;
        }
        if ($detectedModelCode) {
            $this->deviceScan['detected_model_code'] = $detectedModelCode;
        }
        if ($detectedColor) {
            $this->deviceScan['detected_color'] = $detectedColor;
        }
        if ($detectedRam) {
            $this->deviceScan['detected_ram'] = $detectedRam;
        }
        if ($detectedStorage) {
            $this->deviceScan['detected_storage'] = $detectedStorage;
        }

        // If the user hasn't selected a model yet, build a helpful draft specs string
        // from what the box provides (model code / RAM / storage / color).
        if ($this->phoneModelId === '' && $this->deviceSpecs === '') {
            $parts = collect([
                $detectedModelCode ?: null,
                $detectedRam && $detectedStorage ? "{$detectedRam}/{$detectedStorage}" : null,
                $detectedColor ?: null,
            ])->filter()->implode(' - ');

            if ($parts !== '') {
                $this->deviceSpecs = $parts;
            }
        }

        if ($linkedUnit) {
            $expectedImeis = collect([$linkedUnit->imei_1, $linkedUnit->imei_2])->filter()->values();
            $imeiMatches = $detectedImei ? $expectedImeis->contains($detectedImei) : true;
            $serialMatches = $detectedSerial
                ? ($linkedUnit->serial_number
                    ? strtoupper((string) $linkedUnit->serial_number) === strtoupper($detectedSerial)
                    : true)
                : true;

            if (! $imeiMatches || ! $serialMatches) {
                $this->scanFeedbackTone = 'red';
                $this->scanFeedbackMessage = 'Scanned identifiers do not fully match the selected stock unit. Please re-check the sticker photo or choose the correct device.';

                return;
            }

            $this->scanFeedbackTone = 'emerald';
            $this->scanFeedbackMessage = 'Scan confirmed the selected stock unit successfully.';

            return;
        }

        if ($detectedImei) {
            $this->imeiNumber = $detectedImei;
        }

        if ($this->imei2 === '' && $detectedImei2) {
            $this->imei2 = $detectedImei2;
        }

        $this->scanFeedbackTone = 'emerald';
        $this->scanFeedbackMessage = 'Identifiers were captured from the uploaded image and filled automatically.';
    }

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validateStepOne(),
            2 => $this->validateStepTwo(),
            3 => $this->validateStepThree(),
            4 => $this->validateStepFour(),
            5 => $this->validateStepFive(),
            6 => $this->validateStepSix(),
            default => null,
        };

        if ($this->step < 7) {
            $this->step++;
        }
    }

    private function validateStepOne(): void
    {
        $this->validate($this->step1Rules());
        $this->enforceDeviceSelectionIntegrity(
            app(KycDeviceCatalogService::class),
            app(IMEITrackingService::class)
        );
    }

    private function normalizeContactPhones(): void
    {
        $phoneService = app(KycPhoneService::class);
        $primaryPhone = $phoneService->normalizeForField(
            'phone',
            'phoneCountry',
            $this->phone,
            $this->phoneCountry
        );
        $altPhone = $phoneService->normalizeForField(
            'altPhone',
            'altPhoneCountry',
            $this->altPhone,
            $this->altPhoneCountry !== '' ? $this->altPhoneCountry : $this->phoneCountry,
            false
        );

        $errors = [];

        if (Customer::query()->where('phone', $primaryPhone['e164'])->exists()) {
            $errors['phone'] = 'This phone number is already registered.';
        }

        if ($altPhone && $altPhone['e164'] === $primaryPhone['e164']) {
            $errors['altPhone'] = 'Alternative phone must be different from the primary phone.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->phone = $primaryPhone['e164'];
        $this->altPhone = $altPhone['e164'] ?? '';
        $this->paymentPhone = $primaryPhone['e164'];
        $this->phoneMetadata['phone'] = $primaryPhone;

        if ($altPhone) {
            $this->phoneMetadata['alt_phone'] = $altPhone;
        } else {
            unset($this->phoneMetadata['alt_phone']);
        }
    }

    private function normalizeNextOfKinPhones(): void
    {
        $phoneService = app(KycPhoneService::class);
        $nokPhone = $phoneService->normalizeForField(
            'nokPhone',
            'nokPhoneCountry',
            $this->nokPhone,
            $this->nokPhoneCountry
        );
        $nok2Phone = $phoneService->normalizeForField(
            'nok2Phone',
            'nok2PhoneCountry',
            $this->nok2Phone,
            $this->nok2PhoneCountry !== '' ? $this->nok2PhoneCountry : $this->nokPhoneCountry,
            false
        );

        $errors = [];

        if ($this->phone !== '' && $nokPhone['e164'] === $this->phone) {
            $errors['nokPhone'] = 'Next of kin phone must be different from the customer phone.';
        }

        if ($nok2Phone && $nok2Phone['e164'] === $nokPhone['e164']) {
            $errors['nok2Phone'] = 'Secondary next of kin phone must be different from the primary next of kin phone.';
        }

        if ($this->phone !== '' && $nok2Phone && $nok2Phone['e164'] === $this->phone) {
            $errors['nok2Phone'] = 'Secondary next of kin phone must be different from the customer phone.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->nokPhone = $nokPhone['e164'];
        $this->nok2Phone = $nok2Phone['e164'] ?? '';
        $this->phoneMetadata['nok_phone'] = $nokPhone;

        if ($nok2Phone) {
            $this->phoneMetadata['nok2_phone'] = $nok2Phone;
        } else {
            unset($this->phoneMetadata['nok2_phone']);
        }
    }

    private function normalizeAccessorySelection(): void
    {
        $normalizedItems = app(KycAccessoryOfferService::class)->normalize($this->deviceAccessories);
        $errors = [];

        foreach ($normalizedItems as $index => $item) {
            if (($item['name'] ?? '') === '') {
                $errors["deviceAccessories.{$index}.name"] = 'Enter the accessory name.';
            }

            if (($item['offer_type'] ?? 'free') !== 'free' && ! isset($item['unit_price'])) {
                $errors["deviceAccessories.{$index}.unit_price"] = 'Enter a price for charged or discounted accessories.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->deviceAccessories = $normalizedItems;
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

    private function storeSignature(string $dataUrl, string $directory): ?string
    {
        if ($dataUrl === '') {
            return null;
        }

        if (! preg_match('/^data:image\/png;base64,(.+)$/', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[1], true);

        if ($binary === false) {
            return null;
        }

        $path = "kyc/{$directory}/".Str::uuid().'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function setSignature(string $field, string $dataUrl): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        if (! in_array($field, ['customerSignatureData', 'foSignatureData'], true)) {
            return;
        }

        $this->{$field} = $dataUrl;
    }

    public function clearSignature(string $field): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        if (! in_array($field, ['customerSignatureData', 'foSignatureData'], true)) {
            return;
        }

        $this->{$field} = '';
    }

    public function initiateDepositPayment(SelcomCheckoutService $selcom): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $this->normalizeContactPhones();
        $this->validateOnly('paymentPhone', ['paymentPhone' => ['required', 'string', 'min:10', 'max:20']]);

        if ((float) $this->depositAmount <= 0) {
            throw ValidationException::withMessages([
                'depositAmount' => 'Enter a valid deposit amount before sending a payment request.',
            ]);
        }

        $latestPayment = $this->latestDraftPayment;

        if ($latestPayment?->isCompleted()) {
            $this->hydratePaymentBadge($latestPayment);
            $this->dispatch('toast', message: 'This draft already has a successful deposit payment.', type: 'success');

            return;
        }

        try {
            $payment = $selcom->createDraftPayment(
                $this->draftReference,
                $this->paymentPhone !== '' ? $this->paymentPhone : $this->phone,
                (float) $this->depositAmount,
                auth()->id()
            );

            $payment = $selcom->initiateWalletPush($payment, [
                'name' => trim("{$this->firstName} {$this->middleName} {$this->lastName}") !== ''
                    ? trim("{$this->firstName} {$this->middleName} {$this->lastName}")
                    : 'OpticEdge Customer',
                'phone' => $this->paymentPhone !== '' ? $this->paymentPhone : $this->phone,
                'email' => $this->email !== '' ? $this->email : null,
            ], route('api.payments.selcom.webhook'));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'paymentPhone' => $exception->getMessage(),
            ]);
        }

        $this->hydratePaymentBadge($payment);
        $this->dispatch('toast', message: 'Payment prompt sent. Ask the customer to approve it on their phone.', type: 'success');
    }

    public function refreshDepositPaymentStatus(SelcomCheckoutService $selcom): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $payment = $this->latestDraftPayment;

        if (! $payment) {
            throw ValidationException::withMessages([
                'paymentPhone' => 'Start a Selcom payment request first.',
            ]);
        }

        try {
            $payment = $selcom->syncPaymentStatus($payment);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'paymentPhone' => $exception->getMessage(),
            ]);
        }
        $this->hydratePaymentBadge($payment);

        $this->dispatch('toast', message: $payment->isCompleted()
            ? 'Payment confirmed successfully.'
            : 'Payment status refreshed. It is still waiting for completion.', type: $payment->isCompleted() ? 'success' : 'error');
    }

    public function linkDepositTransaction(SelcomCheckoutService $selcom): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $ref = trim($this->linkPaymentReference);

        if ($ref === '') {
            throw ValidationException::withMessages([
                'linkPaymentReference' => 'Enter a Selcom reference, order id, or transid to link.',
            ]);
        }

        $payment = $selcom->findByAnyReference($ref);

        if (! $payment) {
            throw ValidationException::withMessages([
                'linkPaymentReference' => 'No Selcom payment record was found with that reference.',
            ]);
        }

        if (! $payment->isCompleted()) {
            throw ValidationException::withMessages([
                'linkPaymentReference' => 'That payment exists but is not completed yet.',
            ]);
        }

        $payment->forceFill(['draft_reference' => $this->draftReference])->save();
        $this->hydratePaymentBadge($payment->fresh());
        $this->dispatch('toast', message: 'Deposit transaction linked successfully.', type: 'success');
    }

    private function hydratePaymentBadge(?SelcomPaymentRequest $payment): void
    {
        $this->paymentRecordId = $payment?->id;

        if (! $payment) {
            $this->paymentStatus = 'pending';
            $this->paymentMessage = '';

            return;
        }

        $this->paymentStatus = $payment->status;
        $this->paymentMessage = match ($payment->status) {
            'completed' => 'Deposit payment confirmed successfully.',
            'failed' => 'Payment attempt failed or was cancelled.',
            default => 'Payment request sent. Waiting for customer approval.',
        };
    }

    private function ensureFinalSubmissionReadiness(): SelcomPaymentRequest
    {
        $payment = app(SelcomCheckoutService::class)->latestCompletedDraftPayment($this->draftReference);

        if (! $payment) {
            throw ValidationException::withMessages([
                'depositAmount' => 'A successful Selcom deposit payment is required before submitting this application.',
            ]);
        }

        if ($this->agreementDecision !== 'yes') {
            throw ValidationException::withMessages([
                'agreementDecision' => 'Customer must accept the agreement before you can continue.',
            ]);
        }

        if ($this->customerSignatureData === '') {
            throw ValidationException::withMessages([
                'customerSignatureData' => 'Capture the customer signature before submitting.',
            ]);
        }

        if ($this->foSignatureData === '') {
            throw ValidationException::withMessages([
                'foSignatureData' => 'Capture the front officer signature before submitting.',
            ]);
        }

        if (! $this->activeAgreementDocument) {
            throw ValidationException::withMessages([
                'agreementDecision' => 'No active agreement PDF has been uploaded by admin yet.',
            ]);
        }

        return $payment;
    }

    public function getActiveAgreementDocumentProperty(): ?SystemDocument
    {
        return SystemDocument::query()
            ->where('key', 'kyc_customer_agreement')
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function getLatestDraftPaymentProperty(): ?SelcomPaymentRequest
    {
        return SelcomPaymentRequest::query()
            ->where('draft_reference', $this->draftReference)
            ->latest('paid_at')
            ->latest()
            ->first();
    }

    /** @return array<string,mixed> */
    private function submitRules(): array
    {
        return [
            // Step 1
            'brandId' => ['required', 'exists:brands,id'],
            'phoneModelId' => ['required', 'exists:phone_models,id'],
            'deviceSpecs' => ['required', 'string', 'min:3', 'max:200'],
            'imeiNumber' => ['required', 'string', 'digits:15'],
            'imei2' => ['nullable', 'string', 'digits:15'],
            'cashPrice' => ['required', 'numeric', 'min:1'],
            'depositAmount' => ['required', 'numeric', 'min:0'],
            'preferredRepayment' => ['required', 'in:weekly,biweekly,monthly'],
            // Step 2
            'firstName' => ['required', 'string', 'min:2', 'max:60'],
            'middleName' => ['nullable', 'string', 'max:60'],
            'lastName' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before:today'],
            'nidaNumber' => ['required', 'string', 'size:20', 'unique:customers,nida_number'],
            'idType' => ['required', 'in:nida,passport,driving_license,voter_card'],
            // Step 3
            'phone' => ['required', 'string', 'min:7', 'max:20', 'unique:customers,phone'],
            'phoneCountry' => ['required', 'string', 'size:2'],
            'altPhone' => ['nullable', 'string', 'min:7', 'max:20', 'different:phone'],
            'altPhoneCountry' => ['required', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Step 4
            'occupation' => ['required', 'string', 'max:100'],
            'incomePaymentCycle' => ['required', 'in:daily,weekly,biweekly,monthly,irregular'],
            'isPep' => ['boolean'],
            'employer' => ['nullable', 'string', 'max:100'],
            'workLocation' => ['nullable', 'string', 'max:200'],
            'monthlyIncome' => ['required', 'numeric', 'min:0'],
            'monthlyExpenses' => ['nullable', 'numeric', 'min:0'],
            'durationAtWork' => ['nullable', 'string', 'max:60'],
            // Step 5
            'nokName' => ['required', 'string', 'min:2', 'max:100'],
            'nokPhone' => ['required', 'string', 'min:7', 'max:20'],
            'nokPhoneCountry' => ['required', 'string', 'size:2'],
            'nokRelationship' => ['required', 'in:spouse,parent,sibling,friend,relative,other'],
            'nok2Phone' => ['nullable', 'string', 'min:7', 'max:20', 'different:nokPhone'],
            'nok2PhoneCountry' => ['required', 'string', 'size:2'],
            'nok2Name' => ['nullable', 'string', 'max:100'],
            'nok2Relationship' => ['nullable', 'in:spouse,parent,sibling,friend,relative,other'],
            // Step 6
            'termsAccepted' => ['accepted'],
            'dataConsentAccepted' => ['accepted'],
            'callConsentAccepted' => ['accepted'],
            // Final packaging
            'paymentPhone' => ['required', 'string', 'min:10', 'max:20'],
            'agreementDecision' => ['required', 'in:yes,no'],
            'etrReceiptPhoto' => ['required', 'image', 'max:5120'],
            'assetHandoverList' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'assetHandoverNotes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function processApplication(ApplicationAutoCheckService $checker): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $this->normalizeAccessorySelection();
        $this->normalizeContactPhones();
        $this->normalizeNextOfKinPhones();
        $this->validate($this->submitRules());
        $successfulPayment = $this->ensureFinalSubmissionReadiness();
        $catalog = app(KycDeviceCatalogService::class);
        $this->enforceDeviceSelectionIntegrity($catalog, app(IMEITrackingService::class));
        $selectedUnit = $catalog->accessibleUnit(auth()->user(), $this->inventoryUnitId);
        $activeAgreementDocument = $this->activeAgreementDocument;
        $loanTerms = $this->resolvedLoanTermsSnapshot();

        $customer = Customer::create([
            'registered_by' => auth()->id(),
            'dealer_id' => $selectedUnit?->dealer_id,
            'phone_model_id' => $this->phoneModelId ?: null,
            'inventory_unit_id' => $selectedUnit?->id,
            'application_draft_reference' => $this->draftReference,
            // Step 1 – Device
            'device_specs' => trim($this->deviceSpecs),
            'imei_number' => $selectedUnit?->imei_1 ?? strtoupper(trim($this->imeiNumber)),
            'imei_2' => $selectedUnit?->imei_2 ?? ($this->imei2 ? strtoupper(trim($this->imei2)) : null),
            'serial_number' => $this->resolvedSerialForCustomer($selectedUnit),
            'cash_price' => $this->cashPrice,
            'deposit_amount' => $this->depositAmount,
            'preferred_repayment' => $this->preferredRepayment,
            'loan_interest_rate' => $loanTerms['interest_rate'],
            'loan_interest_type' => $loanTerms['interest_type'],
            'loan_duration_weeks' => $loanTerms['duration_weeks'],
            'loan_grace_period_days' => $loanTerms['grace_period_days'],
            'imei_photo_path' => $this->storePhoto($this->imeiPhoto, 'imei'),
            'device_box_photo_path' => $this->storePhoto($this->deviceBoxPhoto, 'device_box'),
            'device_photo_path' => $this->storePhoto($this->devicePhoto, 'device'),
            'device_scan_metadata' => $this->deviceScan !== [] ? $this->deviceScan : null,
            'device_accessories' => $this->deviceAccessories !== [] ? $this->deviceAccessories : null,
            'store_offer_notes' => $this->storeOfferNotes !== '' ? trim($this->storeOfferNotes) : null,
            'metadata' => [
                'loan_terms' => $loanTerms,
                'is_pep' => $this->isPep,
            ],
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
            'phone_metadata' => $this->phoneMetadata !== [] ? $this->phoneMetadata : null,
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
            'agreement_document_id' => $activeAgreementDocument?->id,
            'agreement_accepted' => true,
            'agreement_presented_at' => now(),
            'agreement_decision_at' => now(),
            'customer_signature_path' => $this->storeSignature($this->customerSignatureData, 'customer-signatures'),
            'fo_signature_path' => $this->storeSignature($this->foSignatureData, 'fo-signatures'),
            'asset_handover_list_path' => $this->storePhoto($this->assetHandoverList, 'handover'),
            'etr_receipt_path' => $this->storePhoto($this->etrReceiptPhoto, 'etr'),
            'asset_handover_notes' => $this->assetHandoverNotes !== '' ? trim($this->assetHandoverNotes) : null,
            'deposit_payment_status' => 'completed',
            'deposit_payment_amount' => $successfulPayment->amount,
            'deposit_payment_reference' => $successfulPayment->selcom_reference ?: $successfulPayment->transid,
            'deposit_paid_at' => $successfulPayment->paid_at,
            'asset_release_status' => 'pending',
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

        app(SelcomCheckoutService::class)->attachToCustomer($successfulPayment, $customer);

        // Run automated checks
        $result = $checker->run($customer, $verification);
        $this->autoCheckResult = $result;

        ProcessFaceMatchJob::dispatch($verification->id);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['auto_check_status' => $result['status']])
            ->log("KYC application submitted for {$customer->full_name} — auto-check: {$result['status']}");

        $this->submittedName = $customer->full_name;
        $this->submitted = true;
    }

    private function resolvedSerialForCustomer(?InventoryUnit $selectedUnit): ?string
    {
        if ($selectedUnit?->serial_number) {
            return strtoupper(trim((string) $selectedUnit->serial_number));
        }

        $fromScan = $this->deviceScan['selected_serial'] ?? null;

        if (! is_string($fromScan) || trim($fromScan) === '') {
            return null;
        }

        return strtoupper(trim($fromScan));
    }

    private function enforceDeviceSelectionIntegrity(
        KycDeviceCatalogService $catalog,
        IMEITrackingService $imeiTracking
    ): void {
        $phoneModel = PhoneModel::query()
            ->with('brand')
            ->whereKey($this->phoneModelId)
            ->where('is_active', true)
            ->first();

        if (! $phoneModel) {
            throw ValidationException::withMessages([
                'phoneModelId' => 'Choose a valid phone model.',
            ]);
        }

        if ($this->brandId !== '' && (string) $phoneModel->brand_id !== $this->brandId) {
            throw ValidationException::withMessages([
                'brandId' => 'Selected brand does not match the chosen phone model.',
            ]);
        }

        $this->deviceSpecs = $catalog->buildDeviceSpecs($phoneModel);

        if ((float) $this->depositAmount > (float) $this->cashPrice) {
            throw ValidationException::withMessages([
                'depositAmount' => 'Deposit cannot be greater than the device cash price.',
            ]);
        }

        try {
            $imeiTracking->assertImeiUnique(
                strtoupper(trim($this->imeiNumber)),
                $this->imei2 ? strtoupper(trim($this->imei2)) : null,
                $this->inventoryUnitId !== '' ? $this->inventoryUnitId : null,
            );
        } catch (ValidationException $exception) {
            $mappedErrors = collect($exception->errors())
                ->mapWithKeys(function (array $messages, string $key): array {
                    return [match ($key) {
                        'imei_1' => 'imeiNumber',
                        'imei_2' => 'imei2',
                        default => $key,
                    } => $messages];
                })
                ->all();

            throw ValidationException::withMessages($mappedErrors);
        }
    }

    private function seedLoanTermsFromDefaults(bool $overwrite = false): void
    {
        $defaults = app(CustomerLoanProvisioningService::class)->defaultTerms($this->preferredRepayment);

        if ($overwrite || $this->loanInterestRate === '') {
            $this->loanInterestRate = (string) $defaults['interest_rate'];
        }

        if ($overwrite || $this->loanInterestType === '') {
            $this->loanInterestType = (string) $defaults['interest_type'];
        }

        if ($overwrite || $this->loanDurationWeeks === '') {
            $this->loanDurationWeeks = (string) $defaults['duration_weeks'];
        }

        if ($overwrite || $this->loanGracePeriodDays === '') {
            $this->loanGracePeriodDays = (string) $defaults['grace_period_days'];
        }
    }

    /**
     * @return array{
     *     interest_rate: float,
     *     interest_type: string,
     *     duration_weeks: int,
     *     repayment_frequency: string,
     *     grace_period_days: int,
     *     source: string
     * }
     */
    private function resolvedLoanTermsSnapshot(): array
    {
        $defaults = app(CustomerLoanProvisioningService::class)->defaultTerms($this->preferredRepayment);
        $interestType = in_array($this->loanInterestType, ['flat', 'reducing_balance'], true)
            ? $this->loanInterestType
            : $defaults['interest_type'];

        return [
            'interest_rate' => round((float) ($this->loanInterestRate !== '' ? $this->loanInterestRate : $defaults['interest_rate']), 2),
            'interest_type' => $interestType,
            'duration_weeks' => max(1, (int) ($this->loanDurationWeeks !== '' ? $this->loanDurationWeeks : $defaults['duration_weeks'])),
            'repayment_frequency' => $this->preferredRepayment !== '' ? $this->preferredRepayment : $defaults['repayment_frequency'],
            'grace_period_days' => max(0, (int) ($this->loanGracePeriodDays !== '' ? $this->loanGracePeriodDays : $defaults['grace_period_days'])),
            'source' => ($this->loanInterestRate !== ''
                    || $this->loanInterestType !== ''
                    || $this->loanDurationWeeks !== ''
                    || $this->loanGracePeriodDays !== '')
                ? 'kyc_capture'
                : 'credit_defaults_snapshot',
        ];
    }

    public function startNew(): void
    {
        $this->reset();
        $this->step = 1;
        $this->scanFeedbackTone = 'slate';
        $this->draftReference = (string) Str::uuid();
        $this->draftCode = $this->generateDraftCode();
        $this->preferredRepayment = 'weekly';
        $this->seedLoanTermsFromDefaults(overwrite: true);
        $this->paymentStatus = 'pending';
        $this->paymentMessage = '';
    }

    public function render()
    {
        $recentProfiles = Customer::with('latestVerification')
            ->latest()
            ->take(6)
            ->get();

        $availableBrands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $availableModels = PhoneModel::query()
            ->with('brand')
            ->where('is_active', true)
            ->when($this->brandId !== '', fn ($query) => $query->where('brand_id', $this->brandId))
            ->orderBy('name')
            ->get();
        $availableUnits = collect();
        $selectedPhoneModel = PhoneModel::query()
            ->with('brand')
            ->whereKey($this->phoneModelId ?: null)
            ->first();
        $selectedInventoryUnit = null;
        $phoneCountries = collect(app(KycPhoneService::class)->supportedCountries())
            ->keyBy('iso')
            ->all();
        $accessoryPresets = [];
        $activeAgreementDocument = $this->activeAgreementDocument;
        $latestDraftPayment = $this->latestDraftPayment;

        return view('livewire.kyc.verification-wizard', compact(
            'recentProfiles',
            'availableBrands',
            'availableModels',
            'availableUnits',
            'selectedPhoneModel',
            'selectedInventoryUnit',
            'phoneCountries',
            'accessoryPresets',
            'activeAgreementDocument',
            'latestDraftPayment'
        ))
            ->layout('layouts.app', ['title' => 'KYC Wizard']);
    }

    private function generateDraftCode(): string
    {
        return Str::upper(Str::random(10));
    }
}
