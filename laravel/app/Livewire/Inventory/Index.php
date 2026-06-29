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
    public string $status = 'Available';
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
        Gate::authorize($id ? 'update' : 'create', $e ?? Equipment::class);

        $this->resetValidation();
        $this->editingId = $id;

        if ($e) {
            $this->name = $e->name;
            $this->assetTag = $e->asset_tag;
            $this->category = $e->category;
            $this->serial = $e->serial;
            $this->condition = $e->condition;
            $this->status = $e->status;
            $this->location = (string) $e->location;
            $this->purchaseDate = optional($e->purchase_date)->toDateString() ?? '';
            $this->image = (string) $e->image;
        } else {
            $this->reset(['name', 'assetTag', 'serial', 'location', 'image']);
            $this->category = 'Laptop';
            $this->condition = 'Good';
            $this->status = 'Available';
            $this->purchaseDate = now()->toDateString();
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(
            $this->editingId ? 'update' : 'create',
            $this->editingId ? Equipment::findOrFail($this->editingId) : Equipment::class
        );

        $this->validate([
            'name' => 'required|string|max:120',
            'assetTag' => ['required', 'string', 'max:60', Rule::unique('equipment', 'asset_tag')->ignore($this->editingId)],
            'category' => 'required|string',
            'serial' => ['nullable', 'string', 'max:120', Rule::unique('equipment', 'serial')->ignore($this->editingId)],
            'condition' => 'required|string',
            'status' => 'required|string',
            'location' => 'nullable|string|max:120',
            'purchaseDate' => 'nullable|date',
            'image' => 'nullable|string|max:500',
        ]);

        $data = [
            'name' => $this->name,
            'asset_tag' => $this->assetTag,
            'category' => $this->category,
            'serial' => $this->serial,
            'condition' => $this->condition,
            'status' => $this->status,
            'location' => $this->location,
            'purchase_date' => $this->purchaseDate ?: null,
            'image' => $this->image,
        ];

        if ($this->editingId) {
            $equipment = Equipment::findOrFail($this->editingId);
            $equipment->update($data);
            AuditLog::record('Equipment updated', "{$this->name} ({$this->assetTag}) details edited.", Auth::user());
            session()->flash('toast', ['Equipment updated.', 'ok']);
        } else {
            Equipment::create($data);
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

        $equipment->update(['status' => 'Available']);
        AuditLog::record('Maintenance cleared', "{$equipment->name} returned to available pool.", Auth::user());
        $this->detailId = null;
        session()->flash('toast', ["{$equipment->name} is available again.", 'ok']);
    }

    public function render()
    {
        return view('livewire.inventory.index', [
            'equipment' => Equipment::query()->search($this->q)->orderByDesc('id')->get(),
        ]);
    }
}
