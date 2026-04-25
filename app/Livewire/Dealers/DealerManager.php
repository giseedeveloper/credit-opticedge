<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class DealerManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public ?string $editingDealerId = null;

    public bool $showDeleteModal = false;

    public ?string $deletingDealerId = null;

    public string $formName = '';

    public string $formCode = '';

    public string $formPhone = '';

    public string $formEmail = '';

    public string $formAddress = '';

    public string $formTin = '';

    public string $formCommission = '4.00';

    public string $formStatus = 'active';

    public string $formOwnerUserId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('dealers.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()->canAccess('dealers.create'), 403);
        $this->resetForm();
        $this->formCode = $this->generateUniqueDealerCode();
        $this->editingDealerId = null;
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function openEditModal(string $dealerId): void
    {
        abort_unless(auth()->user()->canAccess('dealers.edit'), 403);

        $dealer = Dealer::query()->findOrFail($dealerId);
        $this->editingDealerId = $dealer->id;
        $this->formName = $dealer->name;
        $this->formCode = $dealer->code;
        $this->formPhone = (string) ($dealer->phone ?? '');
        $this->formEmail = (string) ($dealer->email ?? '');
        $this->formAddress = (string) ($dealer->address ?? '');
        $this->formTin = (string) ($dealer->tin_number ?? '');
        $this->formCommission = (string) $dealer->commission_rate;
        $this->formStatus = $dealer->status;
        $this->formOwnerUserId = (string) ($dealer->owner_user_id ?? '');
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetForm();
        $this->editingDealerId = null;
    }

    public function openDeleteModal(string $dealerId): void
    {
        abort_unless(auth()->user()->canAccess('dealers.delete'), 403);

        $dealer = Dealer::query()->findOrFail($dealerId);
        $this->deletingDealerId = $dealer->id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingDealerId = null;
    }

    public function deleteDealer(): void
    {
        abort_unless(auth()->user()->canAccess('dealers.delete'), 403);

        $dealer = Dealer::query()->findOrFail($this->deletingDealerId);

        $blocked = [];

        if ($dealer->staff()->exists()) {
            $blocked[] = 'staff users';
        }
        if ($dealer->inventoryUnits()->exists()) {
            $blocked[] = 'inventory units';
        }
        if ($dealer->customers()->exists()) {
            $blocked[] = 'customers';
        }
        if ($dealer->loans()->exists()) {
            $blocked[] = 'loans';
        }
        if ($dealer->commissionLedgers()->exists()) {
            $blocked[] = 'commission ledger entries';
        }

        if ($blocked !== []) {
            throw ValidationException::withMessages([
                'deleteDealer' => 'This dealer cannot be deleted because it is linked to: '.implode(', ', $blocked).'.',
            ]);
        }

        $name = $dealer->name;
        $code = $dealer->code;
        $dealer->delete();

        activity('dealers')
            ->performedOn($dealer)
            ->causedBy(auth()->user())
            ->log("Dealer counter deleted: {$name} ({$code})");

        $this->closeDeleteModal();
        $this->dispatch('toast', message: 'Dealer deleted.', type: 'danger');
    }

    public function saveDealer(): void
    {
        abort_unless(auth()->user()->canAccess('dealers.create'), 403);

        $validated = $this->validate($this->createRules());

        Dealer::query()->create([
            'owner_user_id' => $this->nullableUserId($validated['formOwnerUserId'] ?? ''),
            'name' => $validated['formName'],
            'code' => $validated['formCode'],
            'phone' => $validated['formPhone'] ?: null,
            'email' => $validated['formEmail'] ?: null,
            'address' => $validated['formAddress'] ?: null,
            'tin_number' => $validated['formTin'] ?: null,
            'commission_rate' => $validated['formCommission'],
            'status' => $validated['formStatus'],
        ]);

        activity('dealers')
            ->causedBy(auth()->user())
            ->log("Dealer counter created: {$validated['formName']} ({$validated['formCode']})");

        $this->closeCreateModal();
        $this->dispatch('toast', message: 'Dealer added successfully.', type: 'success');
    }

    public function updateDealer(): void
    {
        abort_unless(auth()->user()->canAccess('dealers.edit'), 403);

        $dealer = Dealer::query()->findOrFail($this->editingDealerId);
        $validated = $this->validate($this->editRules($dealer->id));

        $dealer->update([
            'owner_user_id' => $this->nullableUserId($validated['formOwnerUserId'] ?? ''),
            'name' => $validated['formName'],
            'code' => $validated['formCode'],
            'phone' => $validated['formPhone'] ?: null,
            'email' => $validated['formEmail'] ?: null,
            'address' => $validated['formAddress'] ?: null,
            'tin_number' => $validated['formTin'] ?: null,
            'commission_rate' => $validated['formCommission'],
            'status' => $validated['formStatus'],
        ]);

        activity('dealers')
            ->performedOn($dealer)
            ->causedBy(auth()->user())
            ->log("Dealer counter updated: {$dealer->name}");

        $this->closeEditModal();
        $this->dispatch('toast', message: 'Dealer updated successfully.', type: 'success');
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function createRules(): array
    {
        return [
            'formName' => ['required', 'string', 'min:2', 'max:120'],
            'formCode' => ['required', 'string', 'max:32', 'unique:dealers,code'],
            'formPhone' => ['nullable', 'string', 'max:40'],
            'formEmail' => ['nullable', 'email', 'max:120'],
            'formAddress' => ['nullable', 'string', 'max:255'],
            'formTin' => ['nullable', 'string', 'max:64'],
            'formCommission' => ['required', 'numeric', 'between:0,100'],
            'formStatus' => ['required', 'in:active,inactive'],
            'formOwnerUserId' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function editRules(string $dealerId): array
    {
        return [
            'formName' => ['required', 'string', 'min:2', 'max:120'],
            'formCode' => ['required', 'string', 'max:32', 'unique:dealers,code,'.$dealerId],
            'formPhone' => ['nullable', 'string', 'max:40'],
            'formEmail' => ['nullable', 'email', 'max:120'],
            'formAddress' => ['nullable', 'string', 'max:255'],
            'formTin' => ['nullable', 'string', 'max:64'],
            'formCommission' => ['required', 'numeric', 'between:0,100'],
            'formStatus' => ['required', 'in:active,inactive'],
            'formOwnerUserId' => ['nullable', 'exists:users,id'],
        ];
    }

    public function render()
    {
        $dealers = Dealer::query()
            ->with(['ownerUser'])
            ->withCount('staff')
            ->when($this->search !== '', function ($q): void {
                $s = '%'.$this->search.'%';
                $q->where(function ($q) use ($s): void {
                    $q->where('name', 'like', $s)
                        ->orWhere('code', 'like', $s)
                        ->orWhere('phone', 'like', $s)
                        ->orWhere('email', 'like', $s);
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('name')
            ->paginate(15);

        $ownerRoleNames = ['owner', 'dealer'];

        $candidateIds = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $ownerRoleNames))
            ->pluck('id')
            ->all();

        if ($this->showEditModal && $this->editingDealerId) {
            $currentOwnerId = Dealer::query()
                ->whereKey($this->editingDealerId)
                ->value('owner_user_id');

            if ($currentOwnerId) {
                $candidateIds[] = (string) $currentOwnerId;
            }
        }

        $candidateIds = array_values(array_unique($candidateIds));

        $ownerCandidates = User::query()
            ->whereIn('id', $candidateIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $stats = [
            'total' => Dealer::query()->count(),
            'active' => Dealer::query()->where('status', 'active')->count(),
        ];

        return view('livewire.dealers.dealer-manager', compact('dealers', 'ownerCandidates', 'stats'))
            ->layout('layouts.app', ['title' => 'Dealers']);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->formName = '';
        $this->formCode = '';
        $this->formPhone = '';
        $this->formEmail = '';
        $this->formAddress = '';
        $this->formTin = '';
        $this->formCommission = '4.00';
        $this->formStatus = 'active';
        $this->formOwnerUserId = '';
    }

    private function generateUniqueDealerCode(): string
    {
        $n = Dealer::query()->count() + 1;

        do {
            $code = 'DLR-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (Dealer::query()->where('code', $code)->exists());

        return $code;
    }

    private function nullableUserId(string $id): ?string
    {
        $t = trim($id);

        return $t === '' ? null : $t;
    }
}
