<div style="max-width:560px;margin:0 auto">
    <x-toast-flash />

    @if ($submittedRequest)
        <div class="card">
            <div class="card-body">
                <h2 style="margin:0 0 8px">Borrowing request submitted.</h2>
                <p class="hint" style="margin:0 0 16px">Your request is pending admin review.</p>
                <dl class="kv">
                    <dt>Equipment</dt><dd>{{ $submittedRequest['equipment'] }}</dd>
                    <dt>Needed from</dt><dd>{{ $submittedRequest['start_date'] }}</dd>
                    <dt>Return by</dt><dd>{{ $submittedRequest['end_date'] }}</dd>
                    <dt>Status</dt><dd><x-status-pill :status="$submittedRequest['status']" /></dd>
                </dl>
                <div style="margin-top:18px"><a class="btn btn-ghost" href="{{ route('my-requests') }}">View my requests</a></div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <p class="hint" style="margin-top:0">Requesting as {{ auth()->user()->name }}</p>
                <div class="field">
                    <label>Equipment</label>
                    <select wire:model="equipmentId">
                        <option value="">Select available equipment</option>
                        @foreach ($equipment as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} — Asset Tag {{ $item->asset_tag }}</option>
                        @endforeach
                    </select>
                    @error('equipmentId')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror
                </div>
                <div class="form-grid">
                    <div class="field"><label>Needed From</label><input type="date" wire:model.live="startDate">@error('startDate')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    <div class="field"><label>Return By</label><input type="date" wire:model="endDate">@error('endDate')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                </div>
                <div style="margin-top:18px"><button class="btn btn-primary" wire:click="submit" wire:loading.attr="disabled">Submit Request</button></div>
            </div>
        </div>
    @endif
</div>
