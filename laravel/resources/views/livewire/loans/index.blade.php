<div wire:poll.5s>
    <x-toast-flash />

    @if ($overdueCount > 0)
        <div class="card" style="margin-bottom:16px;border-color:#e3b3b6">
            <div class="card-head" style="background:var(--bad-soft);border-bottom-color:#e3b3b6">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a3131c" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                <h3 style="color:#7c0e15">{{ $overdueCount }} overdue item{{ $overdueCount > 1 ? 's' : '' }}</h3>
                <span class="sub" style="color:#7c0e15">Send reminders to borrowers below</span>
            </div>
        </div>
    @endif

    <div class="tbl-wrap"><table>
        <thead><tr><th>Equipment</th><th>Borrower</th><th>Checked out</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($loans as $r)
            @php($od = $r->isOverdue())
            @php($daysLeft = now()->startOfDay()->diffInDays($r->end_date, false))
            <tr wire:key="loan-{{ $r->id }}">
                <td><div class="row-main">{{ $r->equipment->name }}</div><div class="row-sub mono">{{ $r->equipment->asset_tag }}</div></td>
                <td><div class="row-main">{{ $r->user->name }}</div><div class="row-sub">{{ $r->user->email }}</div></td>
                <td class="row-sub">{{ $r->checked_out_at ? $r->checked_out_at->format('M j, Y') : 'Not yet' }}</td>
                <td><div class="row-main">{{ $r->end_date->format('M j, Y') }}</div>
                    <div class="row-sub" style="color:{{ $od ? 'var(--bad)' : ($daysLeft <= 2 ? 'var(--warn)' : 'var(--ink-3)') }}">
                        {{ $od ? abs($daysLeft) . 'd overdue' : $daysLeft . 'd left' }}
                    </div></td>
                <td>
                    @if ($r->status === 'Approved')
                        <x-status-pill status="Reserved" />
                    @else
                        <span class="pill {{ $od ? 'p-bad' : 'p-info' }}">{{ $od ? 'Overdue' : 'Checked Out' }}</span>
                    @endif
                </td>
                <td><div class="actions">
                    @if ($r->status === 'Approved')
                        <button class="btn btn-primary btn-sm" wire:click="checkOut({{ $r->id }})">Check out</button>
                    @else
                        <button class="btn btn-primary btn-sm" wire:click="openCheckIn({{ $r->id }})">Check in</button>
                        @if ($od)
                            <button class="btn btn-ghost btn-sm" wire:click="remind({{ $r->id }})">Remind</button>
                        @endif
                    @endif
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state title="No active loans" sub="Nothing is currently checked out or reserved." /></td></tr>
        @endforelse
        </tbody>
    </table></div>

    @if ($checkingInId)
        @php($ci = $loans->firstWhere('id', $checkingInId))
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
