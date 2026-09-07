<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    private const SESSION_CODE_VERIFIER = 'fdcp_sso.code_verifier';

    private const SESSION_STATE = 'fdcp_sso.state';

    public function redirect(Request $request): RedirectResponse
    {
        $sso = $this->resolveSsoConfig();
        $baseUrl = rtrim($sso['base_url'], '/');

        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->codeChallengeS256($codeVerifier);

        $state = Str::random(32);

        $request->session()->put(self::SESSION_CODE_VERIFIER, $codeVerifier);
        $request->session()->put(self::SESSION_STATE, $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $sso['client_id'],
            'redirect_uri' => $sso['redirect'],
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'scope' => Arr::get($sso, 'scope', 'openid profile email'),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away("{$baseUrl}/oauth/authorize?{$query}");
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $sso = $this->resolveSsoConfig();
            $baseUrl = rtrim($sso['base_url'], '/');

            // If the SSO provider returns an OAuth error, handle it gracefully.
            $oauthError = $request->query('error');
            if (is_string($oauthError) && $oauthError !== '') {
                $description = $request->query('error_description');
                $message = is_string($description) && $description !== ''
                    ? $description
                    : 'SSO login was cancelled or denied.';

                Log::warning('SSO callback returned OAuth error.', [
                    'error' => $oauthError,
                    'description' => is_string($description) ? $description : null,
                ]);

                return $this->failLogin($request, $message);
            }

            $code = $request->query('code');
            $state = $request->query('state');

            if (! is_string($code) || $code === '') {
                return $this->failLogin($request, 'SSO login failed: missing authorization code.');
            }

            $expectedState = $request->session()->pull(self::SESSION_STATE);
            $codeVerifier = $request->session()->pull(self::SESSION_CODE_VERIFIER);

            if (
                ! is_string($expectedState) ||
                $expectedState === '' ||
                ! is_string($state) ||
                $state !== $expectedState
            ) {
                return $this->failLogin($request, 'SSO login failed: invalid session state.');
            }

            if (! is_string($codeVerifier) || $codeVerifier === '') {
                return $this->failLogin($request, 'SSO login failed: PKCE verifier missing.');
            }

            try {
                $tokenResponse = Http::asForm()->post("{$baseUrl}/oauth/token", [
                    'grant_type' => 'authorization_code',
                    'client_id' => $sso['client_id'],
                    'client_secret' => $sso['client_secret'],
                    'redirect_uri' => $sso['redirect'],
                    'code' => $code,
                    'code_verifier' => $codeVerifier,
                ]);
            } catch (\Throwable $e) {
                Log::error('SSO token exchange failed (request error).', [
                    'message' => $e->getMessage(),
                ]);

                return $this->failLogin($request, 'SSO login failed. Please try again.');
            }

            if (! $tokenResponse->ok()) {
                Log::error('SSO token exchange failed.', [
                    'status' => $tokenResponse->status(),
                ]);

                return $this->failLogin($request, 'SSO login failed.');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = data_get($tokenData, 'access_token');

            if (! is_string($accessToken) || $accessToken === '') {
                Log::error('SSO token exchange returned no access_token.', [
                    'status' => $tokenResponse->status(),
                ]);

                return $this->failLogin($request, 'SSO login failed.');
            }

            try {
                $userinfoResponse = Http::withToken($accessToken)
                    ->acceptJson()
                    ->get("{$baseUrl}/api/userinfo");
            } catch (\Throwable $e) {
                Log::error('SSO userinfo request failed (request error).', [
                    'message' => $e->getMessage(),
                ]);

                return $this->failLogin($request, 'SSO login failed. Please try again.');
            }

            if (! $userinfoResponse->ok()) {
                Log::error('SSO userinfo request failed.', [
                    'status' => $userinfoResponse->status(),
                ]);

                return $this->failLogin($request, 'SSO login failed.');
            }

            $userinfo = $userinfoResponse->json();
            $applicationRole = $this->resolveApplicationRole(
                is_array($userinfo) ? $userinfo : []
            );

            if ($applicationRole['role'] === null) {
                Log::warning('SSO access denied.', [
                    'reason' => $applicationRole['reason'],
                ]);

                return $this->failLogin($request, 'Your account does not have access to this application.');
            }

            $email = data_get($userinfo, 'email');
            $name = data_get($userinfo, 'name') ?? data_get($userinfo, 'preferred_username') ?? $email;

            if (! is_string($email) || $email === '') {
                Log::error('SSO userinfo missing email.');

                return $this->failLogin($request, 'SSO login failed: email not provided by SSO.');
            }

            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => is_string($name) && $name !== '' ? $name : 'SSO User',
                    'email' => $email,
                    'password' => Str::random(64),
                    'role' => $applicationRole['role'],
                ]);
            } else {
                // SSO is the source of truth for the application's role and basic profile fields.
                $user->fill([
                    'name' => is_string($name) && $name !== '' ? $name : $user->name,
                    'email' => $email,
                    'role' => $applicationRole['role'],
                ])->save();
            }

            Auth::login($user);
            $request->session()->regenerate();

            $ssoSubject = data_get($userinfo, 'sub');

            Log::info('SSO login successful.', [
                'user_id' => $user->id,
                'role' => $applicationRole['role'],
                'sso_subject' => is_string($ssoSubject) ? $ssoSubject : null,
            ]);

            return redirect()->route('home');
        } catch (\Throwable $e) {
            Log::error('SSO login failed (uncaught error).', [
                'message' => $e->getMessage(),
            ]);

            return $this->failLogin($request, 'SSO login failed. Please try again.');
        }
    }

    private function failLogin(Request $request, string $message): RedirectResponse
    {
        $request->session()->put('sso_error', $message);

        return redirect()->route('login');
    }

    /**
     * @return array{role: 'admin'|'employee'|null, reason: string|null}
     */
    private function resolveApplicationRole(array $userinfo): array
    {
        if (data_get($userinfo, 'is_active') !== true) {
            return ['role' => null, 'reason' => 'inactive_account'];
        }

        $appAccess = data_get($userinfo, 'apps.it_qr_borrowing');
        if (! is_array($appAccess)) {
            return ['role' => null, 'reason' => 'missing_app_access'];
        }

        $ssoRole = data_get($appAccess, 'role');
        if (! is_string($ssoRole) || trim($ssoRole) === '') {
            return ['role' => null, 'reason' => 'unsupported_role'];
        }

        $roleMap = [
            'admin' => 'admin',
            'user' => 'employee',
        ];

        return [
            'role' => $roleMap[$ssoRole] ?? null,
            'reason' => array_key_exists($ssoRole, $roleMap) ? null : 'unsupported_role',
        ];
    }

    private function resolveSsoConfig(): array
    {
        // Your prompt mentions `services.fdcp`, but this repo currently defines `services.fdcp_accounts`.
        $fdcp = config('services.fdcp');
        if (! is_array($fdcp) || empty($fdcp)) {
            $fdcp = config('services.fdcp_accounts');
        }

        $clientId = Arr::get($fdcp, 'client_id');
        $clientSecret = Arr::get($fdcp, 'client_secret');
        $redirect = Arr::get($fdcp, 'redirect');
        $baseUrl = Arr::get($fdcp, 'base_url');

        // Some existing config uses `host` rather than `base_url`.
        if (! is_string($baseUrl) || $baseUrl === '') {
            $baseUrl = Arr::get($fdcp, 'host');
        }

        if (! is_string($clientId) || $clientId === '') {
            throw new \RuntimeException('SSO client_id is not configured.');
        }
        if (! is_string($clientSecret) || $clientSecret === '') {
            throw new \RuntimeException('SSO client_secret is not configured.');
        }
        if (! is_string($redirect) || $redirect === '') {
            throw new \RuntimeException('SSO redirect URI is not configured.');
        }
        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new \RuntimeException('SSO base_url is not configured.');
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect' => $redirect,
            'base_url' => $baseUrl,
            'scope' => Arr::get($fdcp, 'scope'),
        ];
    }

    private function generateCodeVerifier(): string
    {
        // RFC 7636: 43-128 characters from the unreserved URI characters.
        // We'll generate ~64 bytes of entropy and base64url encode.
        return $this->base64UrlEncode(random_bytes(64));
    }

    private function codeChallengeS256(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);

        return $this->base64UrlEncode($hash);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
