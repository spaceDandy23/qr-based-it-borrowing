<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SsoControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'https://sso.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.fdcp_accounts', [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'redirect' => 'http://localhost/auth/callback',
            'host' => $this->baseUrl,
        ]);
    }

    public function test_sso_admin_creates_an_admin_and_redirects_to_the_admin_landing_page(): void
    {
        $this->fakeSso($this->userinfo('admin'));

        $response = $this->ssoCallback();

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'sso-user@example.test', 'role' => 'admin']);
        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }

    public function test_sso_user_creates_an_employee_and_redirects_to_the_employee_landing_page(): void
    {
        $this->fakeSso($this->userinfo('user'));

        $response = $this->ssoCallback();

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'sso-user@example.test', 'role' => 'employee']);
        $this->get(route('home'))->assertRedirect(route('browse'));
    }

    public function test_existing_employee_is_promoted_when_sso_returns_admin(): void
    {
        $user = $this->localUser('employee');
        $this->fakeSso($this->userinfo('admin'));

        $this->ssoCallback()->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_existing_admin_is_downgraded_when_sso_returns_user(): void
    {
        $user = $this->localUser('admin');
        $this->fakeSso($this->userinfo('user'));

        $this->ssoCallback()->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('employee', $user->fresh()->role);
    }

    public function test_missing_it_qr_borrowing_access_denies_login_even_when_other_app_roles_exist(): void
    {
        Log::spy();
        $userinfo = $this->userinfo('admin');
        unset($userinfo['apps']['it_qr_borrowing']);
        $this->fakeSso($userinfo);

        $response = $this->ssoCallback();

        $this->assertDenied($response, 'missing_app_access');
    }

    public function test_unsupported_it_qr_borrowing_role_denies_login(): void
    {
        Log::spy();
        $this->fakeSso($this->userinfo('it_staff'));

        $response = $this->ssoCallback();

        $this->assertDenied($response, 'unsupported_role');
    }

    public function test_inactive_sso_account_denies_login(): void
    {
        Log::spy();
        $userinfo = $this->userinfo('admin');
        $userinfo['is_active'] = false;
        $this->fakeSso($userinfo);

        $response = $this->ssoCallback();

        $this->assertDenied($response, 'inactive_account');
    }

    public function test_success_log_contains_only_operational_fields_not_the_full_userinfo_payload(): void
    {
        Log::spy();
        $userinfo = $this->userinfo('admin');
        $this->fakeSso($userinfo);

        $this->ssoCallback();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($userinfo): bool {
                return $message === 'SSO login successful.'
                    && $context['role'] === 'admin'
                    && $context['sso_subject'] === $userinfo['sub']
                    && ! array_key_exists('userinfo', $context);
            });
    }

    private function ssoCallback()
    {
        return $this->withSession([
            'fdcp_sso.state' => 'valid-state',
            'fdcp_sso.code_verifier' => 'valid-code-verifier',
        ])->get(route('sso.callback', [
            'code' => 'authorization-code',
            'state' => 'valid-state',
        ]));
    }

    private function fakeSso(array $userinfo): void
    {
        Http::fake([
            "{$this->baseUrl}/oauth/token" => Http::response(['access_token' => 'test-access-token']),
            "{$this->baseUrl}/api/userinfo" => Http::response($userinfo),
        ]);
    }

    private function userinfo(string $role): array
    {
        return [
            'sub' => 'sso-subject-123',
            'name' => 'SSO User',
            'email' => 'sso-user@example.test',
            'department' => 'IT',
            'is_active' => true,
            'apps' => [
                'ticketing' => ['role' => 'it_staff'],
                'fdcp_grant' => ['role' => 'admin'],
                'it_qr_borrowing' => ['role' => $role],
            ],
        ];
    }

    private function localUser(string $role): User
    {
        return User::create([
            'name' => 'Existing User',
            'email' => 'sso-user@example.test',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function assertDenied($response, string $reason): void
    {
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('sso_error', 'Your account does not have access to this application.');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('SSO access denied.', ['reason' => $reason]);
    }
}
