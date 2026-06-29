@if (session('toast'))
    <div x-data x-init="
        const message = @js(session('toast')[0]);
        const type = @js(session('toast')[1]);
        const t = document.createElement('div');
        t.className = 'toast ' + type;
        const icons = {
            ok: '<path d=\'M20 6 9 17l-5-5\'/>',
            err: '<path d=\'M18 6 6 18M6 6l12 12\'/>',
            info: '<path d=\'M12 16v-4M12 8h.01\'/><circle cx=\'12\' cy=\'12\' r=\'9\'/>'
        };
        const ti = document.createElement('div');
        ti.className = 'ti';
        ti.innerHTML = '<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#fff\' stroke-width=\'2.6\'>' + icons[type] + '</svg>';
        const span = document.createElement('span');
        span.textContent = message;
        t.appendChild(ti);
        t.appendChild(span);
        document.getElementById('toasts').appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = '.3s'; setTimeout(() => t.remove(), 300); }, 3200);
    "></div>
@endif
