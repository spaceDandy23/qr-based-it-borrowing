<div>
    <div class="toolbar"><div class="grow"></div>
        <a href="{{ route('export.equipment') }}" class="btn btn-ghost"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Inventory CSV</a>
        <a href="{{ route('export.requests') }}" class="btn btn-primary"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Borrowing history CSV</a>
    </div>

    <div class="grid stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
        <div class="stat"><div class="num">{{ $utilization }}%</div><div class="lbl">Utilization rate</div></div>
        <div class="stat"><div class="num">{{ $totalRequests }}</div><div class="lbl">Total requests</div></div>
        <div class="stat"><div class="num">{{ $completedLoans }}</div><div class="lbl">Completed loans</div></div>
        <div class="stat"><div class="num">{{ $onTimeRate }}%</div><div class="lbl">Returned on time</div></div>
    </div>

    <div class="two-col">
        <div class="card"><div class="card-head"><h3>Most borrowed equipment</h3></div>
            <div class="card-body"><div style="height:260px"><canvas id="chTop"></canvas></div></div></div>
        <div class="card"><div class="card-head"><h3>Request outcomes</h3></div>
            <div class="card-body"><div style="height:260px"><canvas id="chOut"></canvas></div></div></div>
    </div>

    <script>
        document.addEventListener('livewire:navigated', initReportCharts);
        document.addEventListener('DOMContentLoaded', initReportCharts);
        function initReportCharts() {
            const topEl = document.getElementById('chTop');
            if (!topEl || topEl.dataset.drawn) return;
            topEl.dataset.drawn = '1';

            new Chart(topEl, {
                type: 'bar',
                data: { labels: @json($topBorrowed->keys()), datasets: [{ data: @json($topBorrowed->values()), backgroundColor: '#5b4a8a', borderRadius: 6, barThickness: 16 }] },
                options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 } }, y: { grid: { display: false }, ticks: { font: { size: 11 } } } }, responsive: true, maintainAspectRatio: false }
            });

            new Chart(document.getElementById('chOut'), {
                type: 'doughnut',
                data: { labels: @json($outcomes->keys()), datasets: [{ data: @json($outcomes->values()), backgroundColor: ['#8a5a12','#3c7a4a','#7a0d14','#5e544d','#a3131c'], borderWidth: 0 }] },
                options: { cutout: '60%', plugins: { legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8, font: { size: 12 } } } }, responsive: true, maintainAspectRatio: false }
            });
        }
    </script>
</div>
