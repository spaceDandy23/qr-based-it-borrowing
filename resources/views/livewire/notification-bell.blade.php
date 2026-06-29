<div style="position:relative" wire:poll.5s>
    <button class="icon-btn" id="bell" wire:click="toggle">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        @if ($unreadCount > 0)
            <span class="dot"></span>
        @endif
    </button>

    @if ($open)
        <div class="notif-panel" id="notifPanel">
            <div class="h">
                <b style="flex:1">Notifications</b>
                @if ($unreadCount > 0)
                    <button class="btn btn-ghost btn-sm" wire:click="markAllRead">Mark all read</button>
                @endif
            </div>
            <div class="notif-list">
                @forelse ($notifications as $n)
                    @php
                        $type = $n->data['type'] ?? 'info';
                        $icon = match ($type) {
                            'ok' => '<path d="M20 6 9 17l-5-5"/>',
                            'bad' => '<path d="M18 6 6 18M6 6l12 12"/>',
                            'warn' => '<path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
                            default => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
                        };
                        $bg = match ($type) {
                            'ok' => 'var(--ok-soft)', 'bad' => 'var(--bad-soft)', 'warn' => 'var(--warn-soft)', default => 'var(--brand-soft)',
                        };
                        $fg = match ($type) {
                            'ok' => 'var(--ok)', 'bad' => 'var(--bad)', 'warn' => 'var(--warn)', default => 'var(--brand)',
                        };
                    @endphp
                    <div class="notif {{ $n->read_at ? '' : 'unread' }}">
                        <div class="ni" style="background:{{ $bg }};color:{{ $fg }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icon !!}</svg>
                        </div>
                        <div>
                            <p>{{ $n->data['text'] ?? '' }}</p>
                            <small>{{ $n->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <div class="notif"><p>No notifications yet.</p></div>
                @endforelse
            </div>
        </div>
    @endif
</div>
