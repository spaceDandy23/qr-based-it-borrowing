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
      <p class="sub">Use your FDCP account to continue.</p>

      @if (session('sso_error'))
        <div class="alert alert-danger" role="alert" style="margin-bottom: 16px;">
          {{ session('sso_error') }}
        </div>
      @endif

      <form aria-label="FDCP Single Sign-On sign-in">
        <a href="{{ route('sso.redirect') }}" class="btn btn-primary btn-block fdcp-sso-btn" role="button">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            <path d="M9 12l2 2 4-5"/>
          </svg>
          <span>Sign in with FDCP SSO</span>
        </a>
      </form>

      <p class="fdcp-sso-redirect">
        You will be redirected to the FDCP Single Sign-On portal. If you're already signed in, you'll be logged in automatically.
      </p>

      <div class="fdcp-divider" role="separator" aria-label="Secure Authentication">
        <span>Secure Authentication</span>
      </div>

      <div class="fdcp-security-note">
        <span class="fdcp-lock" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </span>
        <p>Authentication is handled securely by FDCP Single Sign-On.</p>
      </div>
    </div>
  </div>
</div>
