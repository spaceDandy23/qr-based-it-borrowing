<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — Mister Babadook</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cinzel:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @livewireStyles
</head>
<body>
<div id="app" style="display:grid" x-data="{ navOpen: false }">
  <div class="scrim" :class="{ open: navOpen }" @click="navOpen = false"></div>
  <aside class="sidebar" :class="{ open: navOpen }">
    <div class="brand">
      <div class="mark"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 19.5V5a2 2 0 0 1 2-2h13v15.5"/><path d="M6.5 19H19a2 2 0 0 1 2 2H7a2 2 0 0 1-1.5-3.3Z"/><circle cx="13" cy="9" r="1.4" fill="#fff" stroke="none"/><path d="M11 14c.7-1 3.3-1 4 0"/></svg></div>
      <div><b>Mister Babadook</b><small>IT Borrowing</small></div>
    </div>
    <nav class="nav">
      @auth
        @if (auth()->user()->isAdmin())
          <div class="nav-label">Admin</div>
          <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
          <a href="{{ route('inventory') }}" class="{{ request()->routeIs('inventory') ? 'active' : '' }}">Inventory</a>
          <a href="{{ route('requests') }}" class="{{ request()->routeIs('requests') ? 'active' : '' }}">Requests</a>
          <a href="{{ route('loans') }}" class="{{ request()->routeIs('loans') ? 'active' : '' }}">Loans</a>
          <a href="{{ route('reports') }}" class="{{ request()->routeIs('reports') ? 'active' : '' }}">Reports</a>
          <a href="{{ route('audit') }}" class="{{ request()->routeIs('audit') ? 'active' : '' }}">Audit Log</a>
          <a href="{{ route('users') }}" class="{{ request()->routeIs('users') ? 'active' : '' }}">Users</a>
        @else
          <div class="nav-label">Employee</div>
          <a href="{{ route('browse') }}" class="{{ request()->routeIs('browse') ? 'active' : '' }}">Browse Equipment</a>
          <a href="{{ route('my-requests') }}" class="{{ request()->routeIs('my-requests') ? 'active' : '' }}">My Requests</a>
          <a href="{{ route('history') }}" class="{{ request()->routeIs('history') ? 'active' : '' }}">Borrowing History</a>
        @endif
      @endauth
    </nav>
    <div class="side-foot">
      <div class="side-user">
        <div class="avatar" style="background:{{ \App\Support\Ui::avatarColor(auth()->user()->name) }}">
          {{ \App\Support\Ui::initials(auth()->user()->name) }}
        </div>
        <div class="meta"><b>{{ auth()->user()->name }}</b><span>{{ ucfirst(auth()->user()->role) }}</span></div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="icon-btn" type="submit" style="width:34px;height:34px;margin-left:auto" title="Sign out">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="menu-btn" @click="navOpen = true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
      <div class="page-title"><h1>{{ $title ?? 'Dashboard' }}</h1><p>{{ $subtitle ?? '' }}</p></div>
      <div class="spacer"></div>
      <form class="search" method="GET" action="{{ auth()->user()->isAdmin() ? route('inventory') : route('browse') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
        <input type="text" name="q" placeholder="Search equipment, tags, serials…">
      </form>
      @livewire('notification-bell')
    </header>
    <div class="content">
      {{ $slot }}
    </div>
  </div>
</div>

<div id="toasts"></div>

@livewireScripts
</body>
</html>
