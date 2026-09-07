<div wire:poll.10s>
    <x-toast-flash />

    @if ($active->isNotEmpty())
        <h3 style="font-size:14px;margin:0 0 12px;color:var(--ink-2)">Active ({{ $active->count() }})</h3>
        @foreach ($active as $r)
            @include('livewire.my-requests.card', ['r' => $r])
        @endforeach
    @endif

    @if ($past->isNotEmpty())
        <h3 style="font-size:14px;margin:22px 0 12px;color:var(--ink-2)">Past requests</h3>
        @foreach ($past as $r)
            @include('livewire.my-requests.card', ['r' => $r])
        @endforeach
    @endif

    @if (! $hasAny)
        <x-empty-state title="No requests yet" sub="Browse the catalog to request your first item.">
            <div style="margin-top:14px"><a href="{{ route('browse') }}" class="btn btn-primary">Browse equipment</a></div>
        </x-empty-state>
    @endif

    @if ($extendingId)
        @php($er = \App\Models\Request::with('equipment')->find($extendingId))
        <div class="modal-bg" wire:click.self="$set('extendingId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Request an extension</h3><p>Current due date: {{ $er->end_date->format('M j, Y') }}</p></div><button class="x" wire:click="$set('extendingId', null)">✕</button></div>
                <div class="modal-body">
                    <div class="field"><label>New return date</label><input type="date" wire:model="extensionNewEnd" min="{{ $er->end_date->toDateString() }}"></div>
                    <div class="field"><label>Reason</label><textarea wire:model="extensionReason" rows="2" placeholder="Why do you need more time?"></textarea></div>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('extendingId', null)">Cancel</button><button class="btn btn-primary" wire:click="submitExtension">Send request</button></div>
            </div>
        </div>
    @endif

    @if ($reportingDamageId)
        @php($dr = \App\Models\Request::with('equipment')->find($reportingDamageId))
        <div class="modal-bg" wire:click.self="$set('reportingDamageId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Report damage</h3><p>{{ $dr->equipment->name }} · <span class="mono">{{ $dr->equipment->asset_tag }}</span></p></div><button class="x" wire:click="$set('reportingDamageId', null)">✕</button></div>
                <div class="modal-body">
                    <div class="field"><label>What happened?</label><textarea wire:model="damageDescription" rows="3" placeholder="Describe the damage or malfunction…"></textarea>@error('damageDescription')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    <div class="field"><label>Severity</label><select wire:model="damageSeverity" class="flt" style="width:100%">
                        <option value="Minor">Minor — still usable</option>
                        <option value="Moderate">Moderate — partly working</option>
                        <option value="Severe">Severe — not working</option>
                    </select></div>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('reportingDamageId', null)">Cancel</button><button class="btn btn-danger" wire:click="submitDamage">Submit report</button></div>
            </div>
        </div>
    @endif

    @if ($cancellingId)
        @php($cancel = \App\Models\Request::with('equipment')->find($cancellingId))
        <div class="modal-bg" wire:click.self="$set('cancellingId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Cancel borrowing request</h3><p>{{ $cancel->equipment->name }} · <span class="mono">{{ $cancel->equipment->asset_tag }}</span></p></div><button class="x" wire:click="$set('cancellingId', null)">✕</button></div>
                <div class="modal-body"><p>Cancel this borrowing request? An approved request will release its reserved equipment.</p></div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('cancellingId', null)">Keep request</button><button class="btn btn-danger" wire:click="confirmCancel">Cancel request</button></div>
            </div>
        </div>
    @endif
</div>
