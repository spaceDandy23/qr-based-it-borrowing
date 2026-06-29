<div>
    <div class="grid stats" style="margin-bottom:16px">
        <div class="stat"><div class="ico" style="background:var(--brand-soft);color:var(--brand)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div><div class="num">{{ $total }}</div><div class="lbl">Total assets</div></div>
        <div class="stat"><div class="ico" style="background:var(--ok-soft);color:var(--ok)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div><div class="num">{{ $available }}</div><div class="lbl">Available</div></div>
        <div class="stat"><div class="ico" style="background:var(--brand-soft);color:var(--brand)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M3 12h18M3 17h18"/></svg></div><div class="num">{{ $borrowed }}</div><div class="lbl">Borrowed</div></div>
        <div class="stat"><div class="ico" style="background:var(--bad-soft);color:var(--bad)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></div><div class="num">{{ $overdue }}</div><div class="lbl">Overdue</div></div>
        <div class="stat"><div class="ico" style="background:var(--warn-soft);color:var(--warn)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg></div><div class="num">{{ $pending }}</div><div class="lbl">Pending requests</div></div>
    </div>

    <div class="two-col" style="margin-bottom:16px">
        <div class="card">
            <div class="card-head"><h3>Inventory status</h3><span class="sub">Distribution of all {{ $total }} assets</span></div>
            <div class="card-body"><div style="height:240px"><canvas id="chStatus"></canvas></div></div>
        </div>
        <div class="card">
            <div class="card-head"><h3>By category</h3></div>
            <div class="card-body"><div style="height:240px"><canvas id="chCat"></canvas></div></div>
        </div>
    </div>

    <div class="two-col">
        <div class="card">
            <div class="card-head"><h3>Pending approvals</h3><span class="sub">{{ $pending }} waiting</span>
                <a href="{{ route('requests') }}" class="btn btn-ghost btn-sm" style="margin-left:auto">View all</a></div>
            <div class="card-body" style="padding-top:6px">
                @forelse ($pendingList as $r)
                    <div class="list-row">
                        <div class="ico" style="background:var(--surface-2)">{!! \App\Support\Ui::category($r->equipment->category, 20, '#5e544d') !!}</div>
                        <div class="t"><b>{{ $r->equipment->name }}</b><span>{{ $r->user->name }} · {{ $r->start_date->format('M j') }}–{{ $r->end_date->format('M j') }}</span></div>
                        <div class="actions">
                            <button class="btn btn-primary btn-sm" wire:click="approve({{ $r->id }})">Approve</button>
                            <a href="{{ route('requests') }}" class="btn btn-ghost btn-sm">Reject</a>
                        </div>
                    </div>
                @empty
                    <div class="empty" style="padding:28px"><b>All caught up</b>No requests awaiting review.</div>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h3>Recent activity</h3>
                <a href="{{ route('audit') }}" class="btn btn-ghost btn-sm" style="margin-left:auto">Full log</a></div>
            <div class="card-body" style="padding-top:6px">
                @foreach ($recentLogs as $l)
                    <div class="list-row">
                        <div class="ico" style="background:var(--surface-2);color:var(--ink-2)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg></div>
                        <div class="t"><b>{{ $l->action }}</b><span>{{ $l->detail }}</span></div>
                        <small style="color:var(--ink-3);white-space:nowrap">{{ $l->created_at->format('M j, g:i A') }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:navigated', initCharts);
        document.addEventListener('DOMContentLoaded', initCharts);
        function initCharts() {
            const statusEl = document.getElementById('chStatus');
            if (!statusEl || statusEl.dataset.drawn) return;
            statusEl.dataset.drawn = '1';

            new Chart(statusEl, {
                type: 'doughnut',
                data: {
                    labels: @json($statusCounts->keys()),
                    datasets: [{ data: @json($statusCounts->values()), backgroundColor: ['#3c7a4a','#5b4a8a','#8a5a12','#a3131c','#7a0d14'], borderWidth: 0 }]
                },
                options: { cutout: '62%', plugins: { legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Inter', size: 12 } } } }, responsive: true, maintainAspectRatio: false }
            });

            new Chart(document.getElementById('chCat'), {
                type: 'bar',
                data: {
                    labels: @json($categoryCounts->keys()),
                    datasets: [{ data: @json($categoryCounts->values()), backgroundColor: '#7a0d14', borderRadius: 6, barThickness: 18 }]
                },
                options: { plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 11 } } }, y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 } } }, responsive: true, maintainAspectRatio: false }
            });
        }
    </script>
</div>
