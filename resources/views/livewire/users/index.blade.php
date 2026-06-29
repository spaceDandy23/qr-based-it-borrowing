<div>
    <x-toast-flash />

    <div class="toolbar">
        <div class="input-wrap grow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
            <input class="flt" style="width:100%" placeholder="Search by name or email…" wire:model.live.debounce.300ms="q">
        </div>
        <button class="btn btn-primary" wire:click="openForm">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> Add user
        </button>
    </div>

    @if ($users->isEmpty())
        <x-empty-state title="No users found" sub="Try a different search." />
    @else
        <div class="tbl-wrap"><table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr wire:key="user-{{ $u->id }}">
                    <td><div style="display:flex;align-items:center;gap:10px">
                        <div class="avatar" style="width:30px;height:30px;font-size:11px;background:{{ \App\Support\Ui::avatarColor($u->name) }}">{{ \App\Support\Ui::initials($u->name) }}</div>
                        <div class="row-main">{{ $u->name }}</div>
                    </div></td>
                    <td class="row-sub">{{ $u->email }}</td>
                    <td><span class="chip">{{ ucfirst($u->role) }}</span></td>
                    <td><div class="actions">
                        <button class="icon-btn" style="width:32px;height:32px" title="Edit" wire:click="openForm({{ $u->id }})"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>
                        @if ($u->id !== auth()->id())
                            <button class="icon-btn" style="width:32px;height:32px;color:var(--bad)" title="Delete" wire:click="confirmDelete({{ $u->id }})"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
                        @endif
                    </div></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

    {{-- Add / edit user modal --}}
    @if ($showForm)
        <div class="modal-bg" wire:click.self="$set('showForm', false)">
            <div class="modal">
                <div class="modal-head">
                    <div><h3>{{ $editingId ? 'Edit user' : 'Add user' }}</h3><p>{{ $editingId ? 'Update account details.' : 'Create a new account.' }}</p></div>
                    <button class="x" wire:click="$set('showForm', false)">✕</button>
                </div>
                <div class="modal-body">
                    <div class="field"><label>Name</label><input wire:model="name" placeholder="Full name">@error('name')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    <div class="field"><label>Email</label><input type="email" wire:model="email" placeholder="you@agency.gov">@error('email')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    <div class="field"><label>Role</label><select wire:model="role">
                        <option value="employee">Employee</option>
                        <option value="admin">Admin</option>
                    </select></div>
                    <div class="field">
                        <label>{{ $editingId ? 'New password (leave blank to keep current)' : 'Password' }}</label>
                        <input type="password" wire:model="password" placeholder="••••••••">
                        @error('password')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="modal-foot">
                    <button class="btn btn-ghost" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:click="save">{{ $editingId ? 'Save changes' : 'Create user' }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm modal --}}
    @if ($deletingId)
        @php($del = \App\Models\User::find($deletingId))
        <div class="modal-bg" wire:click.self="$set('deletingId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Delete user</h3><p>This removes the account permanently.</p></div><button class="x" wire:click="$set('deletingId', null)">✕</button></div>
                <div class="modal-body"><p>Delete <b>{{ $del->name }}</b> ({{ $del->email }})? This can't be undone.</p></div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('deletingId', null)">Cancel</button><button class="btn btn-danger" wire:click="delete">Delete</button></div>
            </div>
        </div>
    @endif
</div>
