<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_clears_the_local_session_and_redirects_to_configured_sso_logout(): void
    {
        config()->set('services.fdcp_accounts.logout_url', 'https://sso.test/logout/sso');
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('https://sso.test/logout/sso');

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
