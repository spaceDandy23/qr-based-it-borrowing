<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\SsoController;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Browse\Index as BrowseIndex;
use App\Livewire\Dashboard;
use App\Livewire\History\Index as HistoryIndex;
use App\Livewire\Inventory\Index as InventoryIndex;
use App\Livewire\Loans\Index as LoansIndex;
use App\Livewire\MyRequests\Index as MyRequestsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Requests\Index as RequestsIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');

    // FDCP SSO OAuth2 (Authorization Code + PKCE)
    Route::get('/auth/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
});

Route::get('/auth/callback', [SsoController::class, 'callback'])->name('sso.callback');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->away(config('services.fdcp_accounts.logout_url'));
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        // Keep existing behavior, but avoid PHPStan/IDE false positives.
        return Auth::user()->role === 'admin'
            ? redirect()->route('dashboard')
            : redirect()->route('browse');
    })->name('home');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/inventory', InventoryIndex::class)->name('inventory');
    Route::get('/requests', RequestsIndex::class)->name('requests');
    Route::get('/loans', LoansIndex::class)->name('loans');
    Route::get('/reports', ReportsIndex::class)->name('reports');
    Route::get('/audit', AuditIndex::class)->name('audit');
    Route::get('/users', UsersIndex::class)->name('users');
    Route::get('/equipment/export', [ExportController::class, 'equipment'])->name('export.equipment');
    Route::get('/requests/export', [ExportController::class, 'requests'])->name('export.requests');
});

Route::middleware(['auth', 'role:employee'])->group(function () {
    Route::get('/browse', BrowseIndex::class)->name('browse');
    Route::get('/my-requests', MyRequestsIndex::class)->name('my-requests');
    Route::get('/history', HistoryIndex::class)->name('history');
});
