<?php

namespace App\Livewire\Kyc;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use App\Services\DeviceIdentifierScanService;
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

    public int $step = 1;

    public bool $submitted = false;

    public ?string $submittedName = null;

    /** @var array<string, mixed>|null */
    public ?array $autoCheckResult = null;

    public string $draftReference = '';

    // ── Step 1: Device ────────────────────────────────────────────
    public string $brandId = '';

    public string $phoneModelId = '';

    public string $inventoryUnitId = '';

    public string $inventorySearch = '';

    public string $deviceSpecs = '';

    public string $imeiNumber = '';

    public string $imei2 = '';

    public string $serialNumber = '';

    public string $cashPrice = '';

    public string $depositAmount = '';

    public string $preferredRepayment = '';

    /** @var array<int, array<string, mixed>> */
    public array $deviceAccessories = [];

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

    public string $agreementDecision = '';

    public string $customerSignatureData = '';

    public string $foSignatureData = '';

    /** @var TemporaryUploadedFile|null */
    public $assetHandoverList = null;

    public string $assetHandoverNotes = '';

    // ── Step 7: Submit ────────────────────────────────────────────
    public string $foNotes = '';

    public string $applicationSource = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $this->draftReference = (string) Str::uuid();

        if (auth()->user()->branch_id) {
            $this->branchId = (string) auth()->user()->branch_id;
        }
    }

    /** @return array<string,mixed> */
    private function step1Rules(): array
    {
        return [
            'brandId' => ['required', 'exists:brands,id'],
            'phoneModelId' => ['required', 'exists:phone_models,id'],
            'inventoryUnitId' => ['nullable', 'exists:inventory_units,id'],
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
            'deviceScan' => ['nullable', 'array'],
            'deviceAccessories' => ['nullable', 'array', 'max:8'],
            'deviceAccessories.*.code' => ['nullable', 'string', 'max:60'],
            'deviceAccessories.*.name' => ['nullable', 'string', 'max:60'],
            'deviceAccessories.*.quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'deviceAccessories.*.offer_type' => ['nullable', 'in:free,charged,discounted'],
            'deviceAccessories.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'deviceAccessories.*.notes' => ['nullable', 'string', 'max:160'],
            'storeOfferNotes' => ['nullable', 'string', 'max:500'],
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
            'nokPhone' => ['required', 'string', 'min:7', 'max:20'],
            'nokPhoneCountry' => ['required', 'string', 'size:2'],
            'nokRelationship' => ['required', 'string', 'max:60'],
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
            'imeiNumber',
            'imei2',
            'serialNumber',
            'cashPrice',
            'deviceScan',
            'scanFeedbackMessage',
        ]);
        $this->scanFeedbackTone = 'slate';
    }

    public function updatedPhoneModelId(KycDeviceCatalogService $catalog): void
    {
        $this->reset([
            'inventoryUnitId',
            'inventorySearch',
            'imeiNumber',
            'imei2',
            'serialNumber',
            'deviceScan',
            'scanFeedbackMessage',
        ]);
        $this->scanFeedbackTone = 'slate';

        if ($this->phoneModelId === '') {
            $this->deviceSpecs = '';
            $this->cashPrice = '';

            return;
        }

        $phoneModel = $catalog->accessibleModel(auth()->user(), $this->phoneModelId);

        if (! $phoneModel) {
            $this->addError('phoneModelId', 'Selected model is not available in your accessible stock.');

            return;
        }

        $this->brandId = (string) $phoneModel->brand_id;
        $this->deviceSpecs = $catalog->buildDeviceSpecs($phoneModel);
        $this->cashPrice = (string) ((float) $phoneModel->retail_price);
    }

    public function updatedInventoryUnitId(KycDeviceCatalogService $catalog): void
    {
        if ($this->inventoryUnitId === '') {
            $this->imeiNumber = '';
            $this->imei2 = '';
            $this->serialNumber = '';
            $this->deviceScan = [];
            $this->scanFeedbackMessage = null;
            $this->scanFeedbackTone = 'slate';

            $phoneModel = $catalog->accessibleModel(auth()->user(), $this->phoneModelId);

            if ($phoneModel) {
                $this->deviceSpecs = $catalog->buildDeviceSpecs($phoneModel);
                $this->cashPrice = (string) ((float) $phoneModel->retail_price);
            }

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
        $this->serialNumber = $unit->serial_number ?? '';
        $this->scanFeedbackTone = 'sky';
        $this->scanFeedbackMessage = 'Stock unit linked. IMEI and serial have been loaded automatically from inventory.';
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
        $detectedSerial = $scan['selected_serial'] ?? null;
        $linkedUnit = $catalog->accessibleUnit(auth()->user(), $this->inventoryUnitId);

        if (! $detectedImei && ! $detectedSerial) {
            $this->scanFeedbackTone = 'amber';
            $this->scanFeedbackMessage = 'Uploaded image was received, but no clear IMEI or serial number was detected. You can still type them manually.';

            return;
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

            if ($detectedSerial && ! $linkedUnit->serial_number) {
                $this->serialNumber = strtoupper($detectedSerial);
            }

            $this->scanFeedbackTone = 'emerald';
            $this->scanFeedbackMessage = 'Scan confirmed the selected stock unit successfully.';

            return;
        }

        if ($detectedImei) {
            $this->imeiNumber = $detectedImei;
        }

        if ($detectedSerial) {
            $this->serialNumber = strtoupper($detectedSerial);
        }

        $this->scanFeedbackTone = 'emerald';
        $this->scanFeedbackMessage = 'Identifiers were captured from the uploaded image and filled automatically.';
    }

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validateStepOne(),
            2 => $this->validate($this->step2Rules()),
            3 => $this->validateStepThree(),
            4 => $this->validate($this->step4Rules()),
            5 => $this->validateStepFive(),
            6 => $this->validate($this->step6Rules()),
            default => null,
        };

        if ($this->step < 7) {
            $this->step++;
        }
    }

    private function validateStepOne(): void
    {
        $this->validate($this->step1Rules());
        $this->normalizeAccessorySelection();
        $this->enforceDeviceSelectionIntegrity(
            app(KycDeviceCatalogService::class),
            app(IMEITrackingService::class)
        );
    }

    private function validateStepThree(): void
    {
        $this->normalizeContactPhones();
        $this->validate($this->step3Rules());
    }

    private function validateStepFive(): void
    {
        $this->normalizeNextOfKinPhones();
        $this->validate($this->step5Rules());
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
            'inventoryUnitId' => ['nullable', 'exists:inventory_units,id'],
            'deviceSpecs' => ['required', 'string', 'min:3', 'max:200'],
            'imeiNumber' => ['required', 'string', 'digits:15'],
            'cashPrice' => ['required', 'numeric', 'min:1'],
            'depositAmount' => ['required', 'numeric', 'min:0'],
            'preferredRepayment' => ['required', 'in:weekly,biweekly,monthly'],
            'deviceAccessories' => ['nullable', 'array', 'max:8'],
            'storeOfferNotes' => ['nullable', 'string', 'max:500'],
            // Step 2
            'firstName' => ['required', 'string', 'min:2', 'max:60'],
            'lastName' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
            'nidaNumber' => ['required', 'string', 'size:20', 'unique:customers,nida_number'],
            'idType' => ['required', 'in:nida,passport,driving_license,voter_card'],
            // Step 3
            'phone' => ['required', 'string', 'min:7', 'max:20', 'unique:customers,phone'],
            'phoneCountry' => ['required', 'string', 'size:2'],
            'altPhone' => ['nullable', 'string', 'min:7', 'max:20', 'different:phone'],
            'altPhoneCountry' => ['required', 'string', 'size:2'],
            'branchId' => ['required', 'exists:branches,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Step 4
            'monthlyIncome' => ['required', 'numeric', 'min:0'],
            // Step 5
            'nokName' => ['required', 'string', 'min:2', 'max:100'],
            'nokPhone' => ['required', 'string', 'min:7', 'max:20'],
            'nokPhoneCountry' => ['required', 'string', 'size:2'],
            'nokRelationship' => ['required', 'string', 'max:60'],
            'nok2Phone' => ['nullable', 'string', 'min:7', 'max:20', 'different:nokPhone'],
            'nok2PhoneCountry' => ['required', 'string', 'size:2'],
            // Step 6
            'termsAccepted' => ['accepted'],
            'dataConsentAccepted' => ['accepted'],
            'callConsentAccepted' => ['accepted'],
            // Final packaging
            'paymentPhone' => ['required', 'string', 'min:10', 'max:20'],
            'agreementDecision' => ['required', 'in:yes,no'],
            'assetHandoverList' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
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

        $customer = Customer::create([
            'registered_by' => auth()->id(),
            'branch_id' => $this->branchId ?: null,
            'vendor_id' => $selectedUnit?->vendor_id,
            'phone_model_id' => $this->phoneModelId ?: null,
            'inventory_unit_id' => $selectedUnit?->id,
            'application_draft_reference' => $this->draftReference,
            // Step 1 – Device
            'device_specs' => trim($this->deviceSpecs),
            'imei_number' => $selectedUnit?->imei_1 ?? strtoupper(trim($this->imeiNumber)),
            'imei_2' => $selectedUnit?->imei_2 ?? ($this->imei2 ? strtoupper(trim($this->imei2)) : null),
            'serial_number' => $selectedUnit?->serial_number ?? ($this->serialNumber ? strtoupper(trim($this->serialNumber)) : null),
            'cash_price' => $this->cashPrice,
            'deposit_amount' => $this->depositAmount,
            'preferred_repayment' => $this->preferredRepayment,
            'imei_photo_path' => $this->storePhoto($this->imeiPhoto, 'imei'),
            'device_box_photo_path' => $this->storePhoto($this->deviceBoxPhoto, 'device_box'),
            'device_photo_path' => $this->storePhoto($this->devicePhoto, 'device'),
            'device_scan_metadata' => $this->deviceScan !== [] ? $this->deviceScan : null,
            'device_accessories' => $this->deviceAccessories !== [] ? $this->deviceAccessories : null,
            'store_offer_notes' => $this->storeOfferNotes !== '' ? trim($this->storeOfferNotes) : null,
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

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['auto_check_status' => $result['status']])
            ->log("KYC application submitted for {$customer->full_name} — auto-check: {$result['status']}");

        $this->submittedName = $customer->full_name;
        $this->submitted = true;
    }

    private function enforceDeviceSelectionIntegrity(
        KycDeviceCatalogService $catalog,
        IMEITrackingService $imeiTracking
    ): void {
        $phoneModel = $catalog->accessibleModel(auth()->user(), $this->phoneModelId);

        if (! $phoneModel || (string) $phoneModel->brand_id !== $this->brandId) {
            throw ValidationException::withMessages([
                'phoneModelId' => 'Choose a valid phone model from the selected brand.',
            ]);
        }

        $this->deviceSpecs = $catalog->buildDeviceSpecs($phoneModel);

        if ((float) $this->depositAmount > (float) $this->cashPrice) {
            throw ValidationException::withMessages([
                'depositAmount' => 'Deposit cannot be greater than the device cash price.',
            ]);
        }

        $selectedUnit = $catalog->accessibleUnit(auth()->user(), $this->inventoryUnitId);

        if ($catalog->hasAvailableUnitsFor(auth()->user(), $this->phoneModelId) && ! $selectedUnit) {
            throw ValidationException::withMessages([
                'inventoryUnitId' => 'Select an available stock unit for this device before continuing.',
            ]);
        }

        if ($selectedUnit) {
            if ((string) $selectedUnit->phone_model_id !== $this->phoneModelId) {
                throw ValidationException::withMessages([
                    'inventoryUnitId' => 'The selected stock unit does not belong to the chosen phone model.',
                ]);
            }

            $this->imeiNumber = $selectedUnit->imei_1;
            $this->imei2 = $selectedUnit->imei_2 ?? '';
            $this->serialNumber = $selectedUnit->serial_number ?? '';

            return;
        }

        try {
            $imeiTracking->assertImeiUnique(
                strtoupper(trim($this->imeiNumber)),
                $this->imei2 ? strtoupper(trim($this->imei2)) : null
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

    public function startNew(): void
    {
        $this->reset();
        $this->step = 1;
        $this->scanFeedbackTone = 'slate';
        $this->draftReference = (string) Str::uuid();
        $this->paymentStatus = 'pending';
        $this->paymentMessage = '';

        if (auth()->user()?->branch_id) {
            $this->branchId = (string) auth()->user()->branch_id;
        }
    }

    public function render()
    {
        $recentProfiles = Customer::with('latestVerification')
            ->latest()
            ->take(6)
            ->get();

        $catalog = app(KycDeviceCatalogService::class);
        $availableBrands = $catalog->brandsFor(auth()->user());
        $availableModels = $catalog->modelsFor(auth()->user(), $this->brandId ?: null);
        $availableUnits = $catalog->unitsFor(auth()->user(), $this->phoneModelId ?: null, trim($this->inventorySearch));
        $selectedPhoneModel = $catalog->accessibleModel(auth()->user(), $this->phoneModelId ?: null);
        $selectedInventoryUnit = $catalog->accessibleUnit(auth()->user(), $this->inventoryUnitId ?: null);
        $branches = Branch::orderBy('name')->get();
        $phoneCountries = collect(app(KycPhoneService::class)->supportedCountries())
            ->keyBy('iso')
            ->all();
        $accessoryPresets = app(KycAccessoryOfferService::class)->presetOptions();
        $activeAgreementDocument = $this->activeAgreementDocument;
        $latestDraftPayment = $this->latestDraftPayment;

        return view('livewire.kyc.verification-wizard', compact(
            'recentProfiles',
            'branches',
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
}
