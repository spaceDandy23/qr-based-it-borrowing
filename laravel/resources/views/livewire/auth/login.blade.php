<div id="auth">
  <div class="auth-card">
    <div class="auth-side">
      <div class="logo">
        <div class="mark" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(150deg,#7a0d14,#3a0408);display:grid;place-items:center">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 19.5V5a2 2 0 0 1 2-2h13v15.5"/><path d="M6.5 19H19a2 2 0 0 1 2 2H7a2 2 0 0 1-1.5-3.3Z"/><circle cx="13" cy="9" r="1.4" fill="#fff" stroke="none"/><path d="M11 14c.7-1 3.3-1 4 0"/></svg>
        </div>
        <div><b style="font-size:18px">Mister Babadook</b></div>
      </div>
      <h2>If it's in a word, or it's in a look... you can't get rid of the Babadook.</h2>
      <p>A borrowing system for IT equipment — built for agencies, schools, and teams that need accountable, auditable asset handling. Once an item is checked out, it knows.</p>
      <div class="auth-points">
        <div><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6 9 17l-5-5"/></svg> Request, approve, check-out, and check-in in one flow</div>
        <div><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6 9 17l-5-5"/></svg> QR codes on every item for fast scanning</div>
        <div><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6 9 17l-5-5"/></svg> Overdue tracking, audit logs, and exportable reports</div>
      </div>
    </div>
    <div class="auth-form">
      <h1>Sign in</h1>
      <p class="sub">Enter your credentials below.</p>

      <form wire:submit="login">
        <div class="field">
          <label>Email</label>
          <input type="email" wire:model="email" placeholder="you@agency.gov">
          @error('email') <p class="hint" style="color:var(--bad)">{{ $message }}</p> @enderror
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" wire:model="password" placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
      </form>
    </div>
  </div>
</div>
