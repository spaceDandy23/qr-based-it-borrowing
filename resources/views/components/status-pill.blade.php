@props(['status'])

@php
    $map = [
        'Available' => 'p-ok', 'Checked Out' => 'p-info', 'Reserved' => 'p-reserved',
        'Maintenance' => 'p-warn', 'Damaged' => 'p-bad',
        'Pending' => 'p-warn', 'Approved' => 'p-ok', 'Rejected' => 'p-bad', 'Returned' => 'p-slate',
    ];
    $class = $map[$status] ?? 'p-slate';
@endphp

<span class="pill {{ $class }}">{{ $status }}</span>
