<?php

namespace App\Livewire\MyRequests;

use App\Livewire\Concerns\ManagesRequests;
use App\Models\AuditLog;
use App\Models\DamageReport;
use App\Models\Extension;
use App\Models\Request as LoanRequest;
use App\Models\User;
use App\Notifications\SystemAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'My Requests'])]
class Index extends Component
{
    use ManagesRequests;

    public ?int $cancellingId = null;

    public ?int $extendingId = null;

    public string $extensionNewEnd = '';

    public string $extensionReason = '';

    public ?int $reportingDamageId = null;

    public string $damageDescription = '';

    public string $damageSeverity = 'Minor';

    public function openExtension(int $requestId): void
    {
        $request = LoanRequest::findOrFail($requestId);
        Gate::authorize('requestExtension', $request);

        $this->extendingId = $requestId;
        $this->extensionNewEnd = $request->end_date->copy()->addDays(7)->toDateString();
        $this->extensionReason = '';
    }

    public function openCancel(int $requestId): void
    {
        $request = LoanRequest::findOrFail($requestId);
        Gate::authorize('cancel', $request);

        $this->cancellingId = $requestId;
    }

    public function confirmCancel(): void
    {
        $this->cancel($this->cancellingId);
        $this->cancellingId = null;
    }

    public function submitExtension(): void
    {
        $this->validate([
            'extensionNewEnd' => 'required|date',
            'extensionReason' => 'nullable|string|max:500',
        ]);

        $request = LoanRequest::with('equipment')->findOrFail($this->extendingId);
        Gate::authorize('requestExtension', $request);

        if (! Carbon::parse($this->extensionNewEnd)->gt($request->end_date)) {
            $this->addError('extensionNewEnd', 'The new return date must be after the current due date.');

            return;
        }

        Extension::create([
            'request_id' => $request->id,
            'new_end' => $this->extensionNewEnd,
            'reason' => $this->extensionReason,
            'status' => 'Pending',
            'requested_at' => now(),
        ]);

        $user = Auth::user();
        AuditLog::record('Extension requested', "{$user->name} requested extension for {$request->equipment->name} to ".Carbon::parse($this->extensionNewEnd)->format('M j, Y').'.', $user);

        foreach (User::where('role', 'admin')->get() as $admin) {
            $admin->notify(new SystemAlert("{$user->name} requested an extension for {$request->equipment->name}.", 'info'));
        }

        $this->extendingId = null;
        session()->flash('toast', ['Extension request sent.', 'ok']);
    }

    public function openDamage(int $requestId): void
    {
        $request = LoanRequest::findOrFail($requestId);
        Gate::authorize('reportDamage', $request);

        $this->reportingDamageId = $requestId;
        $this->damageDescription = '';
        $this->damageSeverity = 'Minor';
    }

    public function submitDamage(): void
    {
        $this->validate([
            'damageDescription' => 'required|string|max:1000',
            'damageSeverity' => ['required', 'string', Rule::in(['Minor', 'Moderate', 'Severe'])],
        ]);

        $request = LoanRequest::with('equipment')->findOrFail($this->reportingDamageId);
        Gate::authorize('reportDamage', $request);

        DamageReport::create([
            'request_id' => $request->id,
            'description' => $this->damageDescription,
            'severity' => $this->damageSeverity,
            'reported_at' => now(),
        ]);

        $user = Auth::user();
        $shortDesc = Str::limit($this->damageDescription, 60);
        AuditLog::record('Damage reported', "{$user->name} reported damage on {$request->equipment->name}: {$shortDesc}", $user);

        foreach (User::where('role', 'admin')->get() as $admin) {
            $admin->notify(new SystemAlert("Damage reported on {$request->equipment->name} by {$user->name}.", 'warn'));
        }

        $this->reportingDamageId = null;
        session()->flash('toast', ['Damage report submitted. IT staff notified.', 'info']);
    }

    public function render()
    {
        $mine = LoanRequest::with('equipment', 'extension', 'damageReport')
            ->where('user_id', Auth::id())
            ->latest('created_at')
            ->get();

        return view('livewire.my-requests.index', [
            'active' => $mine->whereIn('status', ['Pending', 'Approved', 'Checked Out']),
            'past' => $mine->whereIn('status', ['Returned', 'Rejected', 'Cancelled']),
            'hasAny' => $mine->isNotEmpty(),
        ]);
    }
}
