@php($overdue = $r->isOverdue())

<div class="card" style="margin-bottom:12px" wire:key="myreq-{{ $r->id }}">
    <div class="card-body" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
        <div style="width:46px;height:46px;border-radius:10px;background:var(--surface-2);display:grid;place-items:center;flex:0 0 auto">
            {!! \App\Support\Ui::category($r->equipment->category, 24, '#5e544d') !!}
        </div>
        <div style="flex:1;min-width:160px">
            <div class="row-main">{{ $r->equipment->name }}</div>
            <div class="row-sub mono">{{ $r->equipment->asset_tag }}</div>
            <div class="row-sub" style="margin-top:3px">{{ $r->start_date->format('M j, Y') }} → {{ $r->end_date->format('M j, Y') }} · {{ \Illuminate\Support\Str::limit($r->purpose, 70) }}</div>
            @if ($r->reject_reason)
                <div class="req-msg" style="margin-top:8px">Reason: {{ $r->reject_reason }}</div>
            @endif
            @if ($r->extension && $r->extension->status === 'Pending')
                <div class="hint" style="color:var(--warn);margin-top:6px">Extension to {{ $r->extension->new_end->format('M j, Y') }} pending review.</div>
            @endif
        </div>
        <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end">
            @if ($overdue)
                <span class="pill p-bad">Overdue</span>
            @else
                <x-status-pill :status="$r->status" />
            @endif
            <div class="actions">
                @if ($r->status === 'Checked Out' && ! $r->extension)
                    <button class="btn btn-ghost btn-sm" wire:click="openExtension({{ $r->id }})">Request extension</button>
                @endif
                @if ($r->status === 'Checked Out' && ! $r->damageReport)
                    <button class="btn btn-ghost btn-sm" wire:click="openDamage({{ $r->id }})">Report damage</button>
                @endif
            </div>
        </div>
    </div>
</div>
