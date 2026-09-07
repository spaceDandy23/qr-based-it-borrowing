<?php

namespace Tests\Feature;

use App\Exceptions\BorrowingStateException;
use App\Models\Equipment;
use App\Models\Extension;
use App\Models\Request as LoanRequest;
use App\Models\User;
use App\Services\BorrowingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_cannot_perform_admin_lifecycle_actions_or_mutate_another_users_request(): void
    {
        $employee = $this->user('employee');
        $otherEmployee = $this->user('employee');
        $request = $this->request($otherEmployee, $this->equipment());

        $this->assertFalse($employee->can('decide', $request));
        $this->assertFalse($employee->can('checkOut', $request));
        $this->assertFalse($employee->can('checkIn', $request));
        $this->assertFalse($employee->can('view', $request));
        $this->assertFalse($employee->can('requestExtension', $request));
    }

    public function test_only_available_equipment_can_be_requested(): void
    {
        $service = app(BorrowingService::class);
        $user = $this->user('employee');
        $available = $this->equipment('Available');

        $request = $service->createRequest($user, $available->id, $this->requestAttributes());

        $this->assertSame('Pending', $request->status);

        foreach (['Reserved', 'Checked Out', 'Maintenance', 'Damaged'] as $status) {
            $equipment = $this->equipment($status);

            try {
                $service->createRequest($user, $equipment->id, $this->requestAttributes());
                $this->fail("{$status} equipment accepted a request.");
            } catch (BorrowingStateException) {
                $this->assertDatabaseMissing('requests', ['equipment_id' => $equipment->id]);
            }
        }
    }

    public function test_same_user_cannot_create_duplicate_active_requests_for_an_item(): void
    {
        $service = app(BorrowingService::class);
        $user = $this->user('employee');
        $equipment = $this->equipment();

        $service->createRequest($user, $equipment->id, $this->requestAttributes());

        $this->expectException(BorrowingStateException::class);
        $service->createRequest($user, $equipment->id, $this->requestAttributes());
    }

    public function test_approval_requires_pending_request_and_available_equipment(): void
    {
        $service = app(BorrowingService::class);
        $request = $this->request($this->user('employee'), $equipment = $this->equipment());

        $service->approve($request->id);

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'Approved']);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Reserved']);

        $this->expectException(BorrowingStateException::class);
        $service->approve($request->id);
    }

    public function test_reserved_or_checked_out_equipment_cannot_be_approved_and_request_stays_pending(): void
    {
        $service = app(BorrowingService::class);

        foreach (['Reserved', 'Checked Out'] as $status) {
            $equipment = $this->equipment($status);
            $request = $this->request($this->user('employee'), $equipment);

            try {
                $service->approve($request->id);
                $this->fail("{$status} equipment was approved.");
            } catch (BorrowingStateException) {
                $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'Pending']);
                $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => $status]);
            }
        }
    }

    public function test_checkout_requires_approved_request_and_reserved_equipment_and_cannot_repeat(): void
    {
        $service = app(BorrowingService::class);
        $equipment = $this->equipment('Reserved');
        $request = $this->request($this->user('employee'), $equipment, 'Approved');

        $service->checkOut($request->id);

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'Checked Out']);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Checked Out']);

        $this->expectException(BorrowingStateException::class);
        $service->checkOut($request->id);
    }

    public function test_checkin_persists_notes_and_returns_normal_equipment_to_available(): void
    {
        $service = app(BorrowingService::class);
        $equipment = $this->equipment('Checked Out');
        $request = $this->request($this->user('employee'), $equipment, 'Checked Out');

        $service->checkIn($request->id, 'Good', 'Returned with charger.');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'Returned',
            'return_condition' => 'Good',
            'return_notes' => 'Returned with charger.',
        ]);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Available', 'condition' => 'Good']);
    }

    public function test_damaged_checkin_moves_equipment_to_maintenance_and_invalid_or_repeated_checkins_fail_safely(): void
    {
        $service = app(BorrowingService::class);
        $equipment = $this->equipment('Checked Out');
        $request = $this->request($this->user('employee'), $equipment, 'Checked Out');

        $service->checkIn($request->id, 'Damaged');

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'Returned', 'return_condition' => 'Poor']);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Maintenance', 'condition' => 'Poor']);

        $this->expectException(BorrowingStateException::class);
        $service->checkIn($request->id, 'Good');
    }

    public function test_invalid_checkin_condition_does_not_change_request_or_equipment(): void
    {
        $service = app(BorrowingService::class);
        $equipment = $this->equipment('Checked Out');
        $request = $this->request($this->user('employee'), $equipment, 'Checked Out');

        try {
            $service->checkIn($request->id, 'Broken');
            $this->fail('Invalid condition was accepted.');
        } catch (BorrowingStateException) {
            $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'Checked Out']);
            $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Checked Out']);
        }
    }

    public function test_extension_must_move_due_date_later_and_can_only_be_resolved_once(): void
    {
        $service = app(BorrowingService::class);
        $equipment = $this->equipment('Checked Out');
        $request = $this->request($this->user('employee'), $equipment, 'Checked Out');
        $invalid = $this->extension($request, $request->end_date->toDateString());

        try {
            $service->resolveExtension($invalid->id, true);
            $this->fail('Non-extending date was accepted.');
        } catch (BorrowingStateException) {
            $this->assertDatabaseHas('extensions', ['id' => $invalid->id, 'status' => 'Pending']);
        }

        $extension = $this->extension($request, $request->end_date->copy()->addDay()->toDateString());
        $service->resolveExtension($extension->id, true);

        $this->assertDatabaseHas('extensions', ['id' => $extension->id, 'status' => 'Approved']);
        $this->assertSame($extension->new_end->toDateString(), $request->fresh()->end_date->toDateString());

        $this->expectException(BorrowingStateException::class);
        $service->resolveExtension($extension->id, true);
    }

    private function user(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'name' => "User {$number}",
            'email' => "user-{$number}@example.test",
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function equipment(string $status = 'Available'): Equipment
    {
        static $number = 0;
        $number++;

        return Equipment::create([
            'name' => "Equipment {$number}",
            'asset_tag' => "ASSET-{$number}",
            'category' => 'Laptop',
            'serial' => "SERIAL-{$number}",
            'condition' => 'Good',
            'status' => $status,
        ]);
    }

    private function request(User $user, Equipment $equipment, string $status = 'Pending'): LoanRequest
    {
        return LoanRequest::create([
            'equipment_id' => $equipment->id,
            'user_id' => $user->id,
            'purpose' => 'Testing lifecycle integrity.',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => $status,
        ]);
    }

    private function extension(LoanRequest $request, string $newEnd): Extension
    {
        return Extension::create([
            'request_id' => $request->id,
            'new_end' => $newEnd,
            'status' => 'Pending',
            'requested_at' => now(),
        ]);
    }

    private function requestAttributes(): array
    {
        return [
            'purpose' => 'Testing lifecycle integrity.',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ];
    }
}
