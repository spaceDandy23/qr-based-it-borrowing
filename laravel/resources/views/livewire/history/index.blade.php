<div>
    @if ($mine->isEmpty())
        <x-empty-state title="No history yet" sub="Your completed loans will appear here." />
    @else
        <div class="tbl-wrap"><table>
            <thead><tr><th>Equipment</th><th>Purpose</th><th>Period</th><th>Status</th><th>Returned</th></tr></thead>
            <tbody>
            @foreach ($mine as $r)
                <tr wire:key="hist-{{ $r->id }}">
                    <td><div class="row-main">{{ $r->equipment->name }}</div><div class="row-sub mono">{{ $r->equipment->asset_tag }}</div></td>
                    <td class="row-sub" style="max-width:260px;color:var(--ink-2)">{{ $r->purpose }}</td>
                    <td class="row-sub">{{ $r->start_date->format('M j, Y') }} → {{ $r->end_date->format('M j, Y') }}</td>
                    <td>
                        @if ($r->isOverdue())
                            <span class="pill p-bad">Overdue</span>
                        @else
                            <x-status-pill :status="$r->status" />
                        @endif
                    </td>
                    <td class="row-sub">
                        @if ($r->returned_at)
                            {{ $r->returned_at->format('M j, Y') }}<br><span style="color:var(--ink-3)">{{ $r->return_condition }}</span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
</div>
