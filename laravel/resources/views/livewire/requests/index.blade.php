<div wire:poll.5s>
    <x-toast-flash />

    <div class="toolbar">
        <div class="seg">
            @foreach ($segments as $s)
                <button class="{{ $filter === $s ? 'active' : '' }}" wire:click="$set('filter', '{{ $s }}')">
                    {{ $s }}@if ($s === 'Pending' && $pendingCount) ({{ $pendingCount }})@endif
                </button>
            @endforeach
        </div>
    </div>

    @if ($list->isEmpty())
        <x-empty-state title="No requests" sub="Nothing matches this filter yet." />
    @else
        <div class="tbl-wrap"><table>
            <thead><tr><th>Equipment</th><th>Requested by</th><th>Purpose</th><th>Period</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach ($list as $r)
                @php($overdue = $r->isOverdue())
                <tr wire:key="req-{{ $r->id }}">
                    <td><div class="row-main">{{ $r->equipment->name ?? '—' }}</div><div class="row-sub mono">{{ $r->equipment->asset_tag ?? '' }}</div></td>
                    <td><div class="row-main">{{ $r->user->name }}</div><div class="row-sub">{{ $r->user->email }}</div></td>
                    <td style="max-width:240px">
                        <div class="row-sub" style="color:var(--ink-2)">{{ $r->purpose }}</div>
                        @if ($r->extension && $r->extension->status === 'Pending')
                            <span class="flag" style="color:var(--warn)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg>Extension requested → {{ $r->extension->new_end->format('M j, Y') }}</span>
                        @endif
                        @if ($r->damageReport)
                            <span class="flag"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>Damage reported</span>
                        @endif
                    </td>
                    <td><div class="row-sub">{{ $r->start_date->format('M j, Y') }}<br>→ {{ $r->end_date->format('M j, Y') }}</div>@if ($overdue)<span class="pill p-bad" style="margin-top:3px">Overdue</span>@endif</td>
                    <td><x-status-pill :status="$r->status" /></td>
                    <td><div class="actions">
                        @if ($r->status === 'Pending')
                            <button class="btn btn-primary btn-sm" wire:click="approve({{ $r->id }})">Approve</button>
                            <button class="btn btn-ghost btn-sm" wire:click="openReject({{ $r->id }})">Reject</button>
                        @elseif ($r->status === 'Approved')
                            <button class="btn btn-primary btn-sm" wire:click="checkOut({{ $r->id }})">Check out</button>
                        @elseif ($r->status === 'Checked Out')
                            <button class="btn btn-primary btn-sm" wire:click="openCheckIn({{ $r->id }})">Check in</button>
                            @if ($r->extension && $r->extension->status === 'Pending')
                                <button class="btn btn-ghost btn-sm" wire:click="resolveExtension({{ $r->extension->id }}, true)">Extend ✓</button>
                            @endif
                        @endif
                    </div></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

    {{-- Reject modal --}}
    @if ($rejectingId)
        <div class="modal-bg" wire:click.self="$set('rejectingId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Reject request</h3><p>Let the requester know why.</p></div><button class="x" wire:click="$set('rejectingId', null)">✕</button></div>
                <div class="modal-body"><div class="field"><label>Reason (optional)</label><textarea wire:model="rejectReason" rows="3" placeholder="e.g. Equipment is reserved for another event that week."></textarea></div></div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('rejectingId', null)">Cancel</button><button class="btn btn-danger" wire:click="confirmReject">Reject request</button></div>
            </div>
        </div>
    @endif

    {{-- Check-in modal --}}
    @if ($checkingInId)
        @php($ci = $list->firstWhere('id', $checkingInId))
        <div class="modal-bg" wire:click.self="$set('checkingInId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Check in equipment</h3><p>{{ $ci->equipment->name }} · <span class="mono">{{ $ci->equipment->asset_tag }}</span></p></div><button class="x" wire:click="$set('checkingInId', null)">✕</button></div>
                <div class="modal-body">
                    <div class="field"><label>Returned condition</label>
                        <select wire:model="checkInCondition" class="flt" style="width:100%">
                            <option>Excellent</option><option>Good</option><option>Fair</option><option>Poor</option><option>Damaged</option>
                        </select></div>
                    <div class="field"><label>Notes (optional)</label><textarea wire:model="checkInNotes" rows="2" placeholder="Any observations on return…"></textarea></div>
                    <p class="hint">Marking the condition as <b>Damaged</b> flags the item for maintenance instead of returning it to the available pool.</p>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('checkingInId', null)">Cancel</button><button class="btn btn-primary" wire:click="confirmCheckIn">Confirm check-in</button></div>
            </div>
        </div>
    @endif
</div>
