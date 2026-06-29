<div>
    <x-toast-flash />

    <div class="toolbar">
        <div class="input-wrap grow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
            <input class="flt" style="width:100%" placeholder="Search equipment…" wire:model.live.debounce.300ms="q">
        </div>
        <select class="flt" wire:model.live="category">
            @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
        </select>
        <div class="seg">
            <button class="{{ $availability === 'All' ? 'active' : '' }}" wire:click="$set('availability', 'All')">All</button>
            <button class="{{ $availability === 'Available' ? 'active' : '' }}" wire:click="$set('availability', 'Available')">Available only</button>
        </div>
    </div>

    @if ($equipment->isEmpty())
        <x-empty-state title="No equipment found" sub="Try a different category or clear your search." />
    @else
        <div class="eq-grid">
            @foreach ($equipment as $e)
                <div class="eq-card" wire:key="card-{{ $e->id }}">
                    <div class="eq-thumb">
                        @if ($e->image)
                            <img src="{{ $e->image }}">
                        @else
                            <div class="ph">{!! \App\Support\Ui::category($e->category, 52) !!}</div>
                        @endif
                    </div>
                    <div class="body">
                        <div style="display:flex;justify-content:space-between;gap:8px;align-items:start">
                            <div class="name">{{ $e->name }}</div><x-status-pill :status="$e->status" />
                        </div>
                        <div class="tag"><span class="chip">{{ $e->category }}</span> <span class="mono" style="margin-left:4px">{{ $e->asset_tag }}</span></div>
                        <div class="tag">{{ $e->location }} · {{ $e->condition }} condition</div>
                        <div class="foot">
                            @if ($e->status === 'Available')
                                <button class="btn btn-primary btn-sm btn-block" wire:click="openBorrow({{ $e->id }})">Request to borrow</button>
                            @else
                                <button class="btn btn-ghost btn-sm btn-block" disabled style="opacity:.6;cursor:not-allowed">Unavailable</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($borrowingId)
        @php($b = \App\Models\Equipment::find($borrowingId))
        <div class="modal-bg" wire:click.self="$set('borrowingId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Request to borrow</h3><p>{{ $b->name }} · <span class="mono">{{ $b->asset_tag }}</span></p></div><button class="x" wire:click="$set('borrowingId', null)">✕</button></div>
                <div class="modal-body">
                    <div class="field"><label>Purpose of borrowing</label><textarea wire:model="purpose" rows="3" placeholder="Describe how you'll use this equipment…"></textarea>@error('purpose')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    <div class="form-grid">
                        <div class="field"><label>Needed from</label><input type="date" wire:model="startDate"></div>
                        <div class="field"><label>Return by</label><input type="date" wire:model="endDate">@error('endDate')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    </div>
                    <p class="hint">Your request goes to IT staff for review. You'll be notified once it's approved.</p>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('borrowingId', null)">Cancel</button><button class="btn btn-primary" wire:click="submitBorrow">Submit request</button></div>
            </div>
        </div>
    @endif
</div>
