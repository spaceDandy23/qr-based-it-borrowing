<?php

namespace Tests\Feature;

use App\Exceptions\BorrowingStateException;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Request as LoanRequest;
use App\Models\User;
use App\Services\RequestCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_cancel_their_own_pending_request_without_changing_equipment(): void
    {
        $employee = $this->user('employee');
        $equipment = $this->equipment('Available');
        $request = $this->request($employee, $equipment, 'Pending');

        app(RequestCancellationService::class)->cancel($request->id, $employee);

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'Cancelled']);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Available']);
        $this->assertSame(1, AuditLog::where('action', 'Request cancelled')->count());
    }

    public function test_employee_can_cancel_their_own_approved_request_and_release_its_reservation(): void
    {
        $employee = $this->user('employee');
        $equipment = $this->equipment('Reserved');
        $request = $this->request($employee, $equipment, 'Approved');

        app(RequestCancellationService::class)->cancel($request->id, $employee);

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'Cancelled']);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Available']);
        $this->assertStringContainsString('Reservation released', AuditLog::where('action', 'Request cancelled')->sole()->detail);
    }

    public function test_admin_can_cancel_pending_or_approved_requests(): void
    {
        $admin = $this->user('admin');
        $employee = $this->user('employee');
        $pending = $this->request($employee, $this->equipment('Available'), 'Pending');
        $reserved = $this->equipment('Reserved');
        $approved = $this->request($employee, $reserved, 'Approved');

        $service = app(RequestCancellationService::class);
        $service->cancel($pending->id, $admin);
        $service->cancel($approved->id, $admin);

        $this->assertDatabaseHas('requests', ['id' => $pending->id, 'status' => 'Cancelled']);
        $this->assertDatabaseHas('requests', ['id' => $approved->id, 'status' => 'Cancelled']);
        $this->assertDatabaseHas('equipment', ['id' => $reserved->id, 'status' => 'Available']);
    }

    public function test_employee_cannot_cancel_another_users_request_or_a_checked_out_request(): void
    {
        $employee = $this->user('employee');
        $other = $this->user('employee');
        $pending = $this->request($other, $this->equipment('Available'), 'Pending');
        $checkedOut = $this->request($employee, $this->equipment('Checked Out'), 'Checked Out');
        $service = app(RequestCancellationService::class);

        foreach ([$pending, $checkedOut] as $request) {
            try {
                $service->cancel($request->id, $employee);
                $this->fail('Invalid cancellation was accepted.');
            } catch (BorrowingStateException) {
                $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => $request->status]);
            }
        }

        $this->assertSame(0, AuditLog::where('action', 'Request cancelled')->count());
    }

    public function test_repeated_cancellation_creates_no_duplicate_audit_entry(): void
    {
        $employee = $this->user('employee');
        $request = $this->request($employee, $this->equipment('Available'), 'Pending');
        $service = app(RequestCancellationService::class);

        $service->cancel($request->id, $employee);

        $this->expectException(BorrowingStateException::class);
        try {
            $service->cancel($request->id, $employee);
        } finally {
            $this->assertSame(1, AuditLog::where('action', 'Request cancelled')->count());
        }
    }

    private function user(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'name' => "User {$number}",
            'email' => "cancel-user-{$number}@example.test",
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function equipment(string $status): Equipment
    {
        static $number = 0;
        $number++;

        return Equipment::create([
            'name' => "Equipment {$number}",
            'asset_tag' => "CANCEL-{$number}",
            'category' => 'Laptop',
            'serial' => "CANCEL-SERIAL-{$number}",
            'condition' => 'Good',
            'status' => $status,
        ]);
    }

    private function request(User $user, Equipment $equipment, string $status): LoanRequest
    {
        return LoanRequest::create([
            'equipment_id' => $equipment->id,
            'user_id' => $user->id,
            'purpose' => 'Cancellation test.',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => $status,
        ]);
    }
}
