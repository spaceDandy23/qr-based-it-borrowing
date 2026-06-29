<?php

namespace App\Livewire\Users;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Users'])]
class Index extends Component
{
    public string $q = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    public ?int $deletingId = null;

    public string $name = '';
    public string $email = '';
    public string $role = 'employee';
    public string $password = '';

    public function openForm(?int $id = null): void
    {
        Gate::authorize($id ? 'update' : 'create', $id ? User::findOrFail($id) : User::class);

        $this->resetValidation();
        $this->editingId = $id;
        $this->password = '';

        if ($id) {
            $u = User::findOrFail($id);
            $this->name = $u->name;
            $this->email = $u->email;
            $this->role = $u->role;
        } else {
            $this->reset(['name', 'email']);
            $this->role = 'employee';
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(
            $this->editingId ? 'update' : 'create',
            $this->editingId ? User::findOrFail($this->editingId) : User::class
        );

        $this->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => 'required|in:admin,employee',
            'password' => $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8',
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->role = $this->role;
            if ($this->password) {
                $user->password = $this->password;
            }
            $user->save();

            AuditLog::record('User updated', "{$user->name} ({$user->email}) details edited.", Auth::user());
            session()->flash('toast', ['User updated.', 'ok']);
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'password' => $this->password,
            ]);

            AuditLog::record('User added', "{$user->name} ({$user->email}) added as {$user->role}.", Auth::user());
            session()->flash('toast', ['User added.', 'ok']);
        }

        $this->showForm = false;
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('delete', User::findOrFail($id));
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        $user = User::findOrFail($this->deletingId);
        Gate::authorize('delete', $user);

        if ($user->requests()->whereIn('status', ['Pending', 'Approved', 'Checked Out'])->exists()) {
            session()->flash('toast', ["Can't delete {$user->name} — they have active requests or loans.", 'err']);
            $this->deletingId = null;

            return;
        }

        AuditLog::record('User deleted', "{$user->name} ({$user->email}) removed.", Auth::user());
        $user->delete();
        $this->deletingId = null;
        session()->flash('toast', ['User deleted.', 'info']);
    }

    public function render()
    {
        return view('livewire.users.index', [
            'users' => User::query()
                ->when($this->q, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->q}%")
                    ->orWhere('email', 'like', "%{$this->q}%")))
                ->orderBy('name')
                ->get(),
        ]);
    }
}
