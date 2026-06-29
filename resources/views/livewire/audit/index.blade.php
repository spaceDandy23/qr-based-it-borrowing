<div wire:poll.10s>
    <div class="tbl-wrap"><table>
        <thead><tr><th style="width:170px">When</th><th style="width:160px">Actor</th><th style="width:170px">Action</th><th>Detail</th></tr></thead>
        <tbody>
        @foreach ($logs as $l)
            <tr wire:key="log-{{ $l->id }}">
                <td class="row-sub">{{ $l->created_at->format('M j, Y g:i A') }}</td>
                <td><div style="display:flex;align-items:center;gap:8px">
                    <div class="avatar" style="width:24px;height:24px;font-size:10px;background:{{ \App\Support\Ui::avatarColor($l->actor_name) }}">{{ \App\Support\Ui::initials($l->actor_name) }}</div>
                    {{ $l->actor_name }}
                </div></td>
                <td><span class="chip">{{ $l->action }}</span></td>
                <td class="row-sub" style="color:var(--ink-2)">{{ $l->detail }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
