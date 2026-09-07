<?php

namespace App\Livewire\Inventory;

use App\Models\AuditLog;
use App\Models\Equipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Inventory'])]
class Index extends Component
{
    public string $q = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    public ?int $detailId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public string $assetTag = '';

    public string $category = 'Laptop';

    public string $serial = '';

    public string $condition = 'Good';

    public string $location = '';

    public string $purchaseDate = '';

    public string $image = '';

    public function mount()
    {
        $this->q = request()->query('q', '');
    }

    public function openForm(?int $id = null): void
    {
        $e = $id ? Equipment::findOrFail($id) : null;

        if ($e?->status === 'Checked Out') {
            session()->flash('toast', ['Checked Out equipment cannot be edited while it is currently borrowed.', 'err']);

            return;
        }

        Gate::authorize($id ? 'update' : 'create', $e ?? Equipment::class);

        $this->resetValidation();
        $this->editingId = $id;

        if ($e) {
            $this->name = $e->name;
            $this->assetTag = $e->asset_tag;
            $this->category = $e->category;
            $this->serial = $e->serial;
            $this->condition = $e->condition;
            $this->location = (string) $e->location;
            $this->purchaseDate = optional($e->purchase_date)->toDateString() ?? '';
            $this->image = (string) $e->image;
        } else {
            $this->reset(['name', 'assetTag', 'serial', 'location', 'image']);
            $this->category = 'Laptop';
            $this->condition = 'Good';
            $this->purchaseDate = now()->toDateString();
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        $equipment = $this->editingId ? Equipment::findOrFail($this->editingId) : null;

        if ($equipment?->status === 'Checked Out') {
            session()->flash('toast', ['Checked Out equipment cannot be edited while it is currently borrowed.', 'err']);

            return;
        }

        Gate::authorize(
            $this->editingId ? 'update' : 'create',
            $equipment ?? Equipment::class
        );

        $this->validate([
            'name' => 'required|string|max:120',
            'assetTag' => ['required', 'string', 'max:60', Rule::unique('equipment', 'asset_tag')->ignore($this->editingId)],
            'category' => 'required|string',
            'serial' => ['nullable', 'string', 'max:120', Rule::unique('equipment', 'serial')->ignore($this->editingId)],
            'condition' => ['required', 'string', Rule::in(['Excellent', 'Good', 'Fair', 'Poor'])],
            'location' => 'nullable|string|max:120',
            'purchaseDate' => 'nullable|date',
            'image' => 'nullable|string|max:500',
        ]);

        $data = [
            'name' => $this->name,
            'asset_tag' => $this->assetTag,
            'category' => $this->category,
            'serial' => $this->serial ?: null,
            'condition' => $this->condition,
            'location' => $this->location ?: null,
            'purchase_date' => $this->purchaseDate ?: null,
            'image' => $this->image ?: null,
        ];

        if ($this->editingId) {
            $equipment->fill($data);
            $changes = $equipment->getDirty();

            if ($changes) {
                $detail = $this->equipmentChangeDetail($equipment, $changes);
                $equipment->save();
                AuditLog::record('Equipment updated', $detail, Auth::user());
                session()->flash('toast', ['Equipment updated.', 'ok']);
            } else {
                session()->flash('toast', ['No equipment changes to save.', 'info']);
            }
        } else {
            Equipment::create($data + ['status' => 'Available']);
            AuditLog::record('Equipment added', "{$this->name} ({$this->assetTag}) added to inventory.", Auth::user());
            session()->flash('toast', ['Equipment added.', 'ok']);
        }

        $this->showForm = false;
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
    }

    public function editFromDetail(int $id): void
    {
        $this->detailId = null;
        $this->openForm($id);
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('delete', Equipment::findOrFail($id));
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        $equipment = Equipment::findOrFail($this->deletingId);
        Gate::authorize('delete', $equipment);

        if (in_array($equipment->status, ['Checked Out', 'Reserved'])) {
            session()->flash('toast', ["Can't delete an item that's reserved or on loan.", 'err']);
            $this->deletingId = null;

            return;
        }

        AuditLog::record('Equipment deleted', "{$equipment->name} ({$equipment->asset_tag}) removed from inventory.", Auth::user());
        $equipment->delete();
        $this->deletingId = null;
        session()->flash('toast', ['Equipment deleted.', 'info']);
    }

    public function setMaintenance(int $id): void
    {
        $equipment = Equipment::findOrFail($id);
        Gate::authorize('manageMaintenance', $equipment);

        if (in_array($equipment->status, ['Checked Out', 'Reserved'])) {
            session()->flash('toast', ['Item is on loan — check it in first.', 'err']);

            return;
        }

        $equipment->update(['status' => 'Maintenance']);
        AuditLog::record('Maintenance', "{$equipment->name} marked under maintenance.", Auth::user());
        $this->detailId = null;
        session()->flash('toast', ["{$equipment->name} marked for maintenance.", 'info']);
    }

    public function clearMaintenance(int $id): void
    {
        $equipment = Equipment::findOrFail($id);
        Gate::authorize('manageMaintenance', $equipment);

        if ($equipment->status !== 'Maintenance') {
            session()->flash('toast', ['Only equipment under maintenance can be returned to service.', 'err']);

            return;
        }

        $equipment->update(['status' => 'Available']);
        AuditLog::record('Maintenance cleared', "{$equipment->name} returned to available pool in {$equipment->condition} condition.", Auth::user());
        $this->detailId = null;
        session()->flash('toast', ["{$equipment->name} is available again.", 'ok']);
    }

    private function equipmentChangeDetail(Equipment $equipment, array $changes): string
    {
        $labels = [
            'asset_tag' => 'Asset tag',
            'name' => 'Name',
            'category' => 'Category',
            'serial' => 'Serial',
            'condition' => 'Condition',
            'location' => 'Location',
            'purchase_date' => 'Purchase date',
            'image' => 'Image',
        ];

        $details = collect($changes)->map(function ($newValue, string $field) use ($equipment, $labels) {
            $oldValue = $equipment->getRawOriginal($field);

            return sprintf(
                '%s: %s -> %s',
                $labels[$field] ?? str($field)->headline(),
                $oldValue ?: '—',
                $newValue ?: '—',
            );
        });

        return "{$equipment->name} ({$equipment->asset_tag}) updated. ".$details->implode('; ');
    }

    public function render()
    {
        return view('livewire.inventory.index', [
            'equipment' => Equipment::query()->search($this->q)->orderByDesc('id')->get(),
        ]);
    }
}
