<?php

namespace Tests\Feature;

use App\Exceptions\BorrowingStateException;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\User;
use App\Services\EquipmentLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EquipmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_available_equipment_as_maintenance_or_damaged_and_move_damaged_to_maintenance(): void
    {
        $admin = $this->admin();
        $available = $this->equipment('Available');
        $damaged = $this->equipment('Damaged');
        $service = app(EquipmentLifecycleService::class);

        $service->markMaintenance($available->id, $admin);
        $this->assertDatabaseHas('equipment', ['id' => $available->id, 'status' => 'Maintenance']);

        $availableForDamage = $this->equipment('Available');
        $service->markDamaged($availableForDamage->id, $admin);
        $this->assertDatabaseHas('equipment', ['id' => $availableForDamage->id, 'status' => 'Damaged']);

        $service->markMaintenance($damaged->id, $admin);
        $this->assertDatabaseHas('equipment', ['id' => $damaged->id, 'status' => 'Maintenance']);
    }

    #[DataProvider('supportedConditions')]
    public function test_return_to_service_sets_selected_condition_and_available_status_once(string $condition): void
    {
        $equipment = $this->equipment('Maintenance', 'Poor');

        app(EquipmentLifecycleService::class)->returnToService($equipment->id, $condition, $this->admin());

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'status' => 'Available', 'condition' => $condition]);
        $this->assertSame(1, AuditLog::where('action', 'Maintenance resolved')->count());
    }

    public static function supportedConditions(): array
    {
        return [['Excellent'], ['Good'], ['Fair'], ['Poor']];
    }

    public function test_return_to_service_rejects_non_maintenance_or_employee_actions_without_audit(): void
    {
        $available = $this->equipment('Available');
        $maintenance = $this->equipment('Maintenance');
        $service = app(EquipmentLifecycleService::class);

        foreach ([[$available, $this->admin()], [$maintenance, $this->employee()]] as [$equipment, $actor]) {
            try {
                $service->returnToService($equipment->id, 'Excellent', $actor);
                $this->fail('Invalid lifecycle action was accepted.');
            } catch (BorrowingStateException) {
                // Expected.
            }
        }

        $this->assertSame(0, AuditLog::where('action', 'Maintenance resolved')->count());
    }

    public function test_employee_cannot_mark_equipment_as_maintenance_or_damaged(): void
    {
        $employee = $this->employee();
        $available = $this->equipment('Available');
        $service = app(EquipmentLifecycleService::class);

        foreach (['markMaintenance', 'markDamaged'] as $method) {
            try {
                $service->{$method}($available->id, $employee);
                $this->fail('Employee lifecycle action was accepted.');
            } catch (BorrowingStateException) {
                $this->assertDatabaseHas('equipment', ['id' => $available->id, 'status' => 'Available']);
            }
        }

        $this->assertSame(0, AuditLog::count());
    }

    private function admin(): User
    {
        return $this->user('admin');
    }

    private function employee(): User
    {
        return $this->user('employee');
    }

    private function user(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'name' => "User {$number}",
            'email' => "lifecycle-user-{$number}@example.test",
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function equipment(string $status, string $condition = 'Good'): Equipment
    {
        static $number = 0;
        $number++;

        return Equipment::create([
            'name' => "Equipment {$number}",
            'asset_tag' => "LIFECYCLE-{$number}",
            'category' => 'Laptop',
            'serial' => "LIFECYCLE-SERIAL-{$number}",
            'condition' => $condition,
            'status' => $status,
        ]);
    }
}
