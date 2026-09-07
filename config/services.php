<?php

return [

    'fdcp_accounts' => [
        'client_id' => env('FDCP_ACCOUNTS_CLIENT_ID'),
        'client_secret' => env('FDCP_ACCOUNTS_CLIENT_SECRET'),
        'redirect' => env('FDCP_ACCOUNTS_REDIRECT_URI'),
        'host' => env('FDCP_ACCOUNTS_BASE_URL'),
        'logout_url' => env('FDCP_SSO_LOGOUT_URL', 'https://sso.fdcp.ph/logout/sso'),
    ],
];
