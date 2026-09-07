<?php

namespace Tests\Feature;

use App\Livewire\Inventory\Index;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EquipmentEditingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('editableStatuses')]
    public function test_admin_can_edit_equipment_that_is_not_checked_out(string $status): void
    {
        $equipment = $this->equipment($status);

        $this->edit($equipment, ['name' => "Updated {$status} equipment"]);

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'name' => "Updated {$status} equipment"]);
    }

    public static function editableStatuses(): array
    {
        return [['Available'], ['Reserved'], ['Maintenance'], ['Damaged']];
    }

    #[DataProvider('conditionEditableStatuses')]
    public function test_admin_can_change_condition_when_equipment_is_not_checked_out(string $status): void
    {
        $equipment = $this->equipment($status, 'Poor');

        $this->edit($equipment, ['condition' => 'Good']);

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'condition' => 'Good']);
    }

    public static function conditionEditableStatuses(): array
    {
        return [['Available'], ['Maintenance'], ['Damaged']];
    }

    public function test_checked_out_equipment_cannot_be_edited_in_the_ui_or_through_a_crafted_save(): void
    {
        $equipment = $this->equipment('Checked Out');
        $admin = $this->admin();

        $this->assertFalse($admin->can('update', $equipment));

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openForm', $equipment->id)
            ->assertSet('showForm', false);

        $this->craftedFormFor($equipment, $admin)
            ->set('name', 'Attempted checked-out edit')
            ->call('save');

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'name' => $equipment->name, 'condition' => 'Good']);
        $this->assertSame(0, AuditLog::where('action', 'Equipment updated')->count());
    }

    public function test_invalid_condition_is_rejected_without_an_update_or_audit_entry(): void
    {
        $equipment = $this->equipment('Available');

        $this->formFor($equipment)
            ->set('condition', 'Broken')
            ->call('save')
            ->assertHasErrors('condition');

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'condition' => 'Good']);
        $this->assertSame(0, AuditLog::where('action', 'Equipment updated')->count());
    }

    public function test_real_edit_creates_one_meaningful_audit_entry_even_when_multiple_fields_change(): void
    {
        $equipment = $this->equipment('Available', 'Poor');

        $this->edit($equipment, ['condition' => 'Good', 'location' => 'IT Office']);

        $audit = AuditLog::where('action', 'Equipment updated')->sole();
        $this->assertStringContainsString('Condition: Poor -> Good', $audit->detail);
        $this->assertStringContainsString('Location: Storage Room -> IT Office', $audit->detail);
    }

    public function test_admin_can_edit_maintenance_condition_then_return_equipment_to_service(): void
    {
        $equipment = $this->equipment('Maintenance', 'Poor');
        $admin = $this->admin();

        $this->edit($equipment, ['condition' => 'Good']);
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'condition' => 'Good', 'status' => 'Maintenance']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openReturnToService', $equipment->id)
            ->set('returnToServiceCondition', 'Good')
            ->call('returnToService');

        $this->assertDatabaseHas('equipment', [
            'id' => $equipment->id,
            'condition' => 'Good',
            'status' => 'Available',
        ]);
    }

    public function test_no_op_and_repeated_identical_saves_do_not_create_duplicate_audit_entries(): void
    {
        $equipment = $this->equipment('Available');

        $this->formFor($equipment)->call('save');
        $this->assertSame(0, AuditLog::where('action', 'Equipment updated')->count());

        $form = $this->formFor($equipment)->set('location', 'IT Office');
        $form->call('save');
        $form->call('save');

        $this->assertSame(1, AuditLog::where('action', 'Equipment updated')->count());
    }

    private function edit(Equipment $equipment, array $changes): void
    {
        $form = $this->formFor($equipment);

        foreach ($changes as $field => $value) {
            $form->set($field, $value);
        }

        $form->call('save')->assertHasNoErrors();
    }

    private function formFor(Equipment $equipment, ?User $admin = null)
    {
        $admin ??= $this->admin();

        return Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openForm', $equipment->id)
            ->assertSet('showForm', true);
    }

    private function craftedFormFor(Equipment $equipment, User $admin)
    {
        return Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('editingId', $equipment->id)
            ->set('name', $equipment->name)
            ->set('assetTag', $equipment->asset_tag)
            ->set('category', $equipment->category)
            ->set('serial', $equipment->serial)
            ->set('condition', $equipment->condition)
            ->set('location', $equipment->location ?? '')
            ->set('purchaseDate', optional($equipment->purchase_date)->toDateString() ?? '')
            ->set('image', $equipment->image ?? '');
    }

    private function admin(): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'name' => "Admin {$number}",
            'email' => "admin-{$number}@example.test",
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function equipment(string $status, string $condition = 'Good'): Equipment
    {
        static $number = 0;
        $number++;

        return Equipment::create([
            'name' => "Equipment {$number}",
            'asset_tag' => "EDIT-{$number}",
            'category' => 'Laptop',
            'serial' => "EDIT-SERIAL-{$number}",
            'condition' => $condition,
            'status' => $status,
            'location' => 'Storage Room',
        ]);
    }
}
