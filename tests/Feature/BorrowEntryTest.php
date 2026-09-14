<?php

namespace Tests\Feature;

use App\Livewire\Borrow\Index as BorrowIndex;
use App\Models\Equipment;
use App\Models\User;
use App\Notifications\SystemAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class BorrowEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_borrow_route_requires_authentication_and_is_available_to_employees(): void
    {
        $this->get(route('borrow'))->assertRedirect(route('login'));

        $this->actingAs($this->user('employee'))
            ->get(route('borrow'))
            ->assertOk()
            ->assertSeeLivewire(BorrowIndex::class);
    }

    public function test_only_available_equipment_is_shown_in_the_borrow_form(): void
    {
        $available = $this->equipment('Available');
        $ineligible = collect(['Reserved', 'Checked Out', 'Maintenance', 'Damaged'])
            ->map(fn (string $status) => $this->equipment($status));

        $component = Livewire::actingAs($this->user('employee'))->test(BorrowIndex::class);
        $component->assertSee($available->name);
        $ineligible->each(fn (Equipment $equipment) => $component->assertDontSee($equipment->name));
    }

    public function test_start_date_recalculates_the_two_day_weekday_return_default(): void
    {
        $component = Livewire::actingAs($this->user('employee'))->test(BorrowIndex::class);

        $component->set('startDate', '2026-09-07')->assertSet('endDate', '2026-09-09');
        $component->set('startDate', '2026-09-10')->assertSet('endDate', '2026-09-14');
        $component->set('startDate', '2026-09-11')->assertSet('endDate', '2026-09-14');
    }

    public function test_submission_creates_a_pending_request_for_the_authenticated_user_and_notifies_admins(): void
    {
        Notification::fake();
        $employee = $this->user('employee');
        $admin = $this->user('admin');
        $equipment = $this->equipment('Available');

        Livewire::actingAs($employee)
            ->test(BorrowIndex::class)
            ->set('equipmentId', (string) $equipment->id)
            ->set('startDate', '2026-09-07')
            ->set('endDate', '2026-09-09')
            ->call('submit')
            ->assertSet('submittedRequest.status', 'Pending');

        $request = $employee->requests()->sole();
        $this->assertSame($equipment->id, $request->equipment_id);
        $this->assertSame('Pending', $request->status);
        $this->assertSame('2026-09-07', $request->start_date->toDateString());
        $this->assertSame('2026-09-09', $request->end_date->toDateString());
        Notification::assertSentTo($admin, SystemAlert::class);
    }

    public function test_weekend_return_dates_are_normalized_and_invalid_ranges_are_rejected(): void
    {
        $employee = $this->user('employee');
        $equipment = $this->equipment('Available');

        Livewire::actingAs($employee)
            ->test(BorrowIndex::class)
            ->set('equipmentId', (string) $equipment->id)
            ->set('startDate', '2026-09-11')
            ->set('endDate', '2026-09-12')
            ->call('submit');

        $this->assertSame('2026-09-14', $employee->requests()->sole()->end_date->toDateString());

        Livewire::actingAs($employee)
            ->test(BorrowIndex::class)
            ->set('equipmentId', (string) $this->equipment('Available')->id)
            ->set('startDate', '2026-09-10')
            ->set('endDate', '2026-09-09')
            ->call('submit')
            ->assertHasErrors('endDate');
    }

    public function test_stale_or_repeated_submission_does_not_create_duplicate_requests(): void
    {
        $employee = $this->user('employee');
        $equipment = $this->equipment('Available');
        $component = Livewire::actingAs($employee)
            ->test(BorrowIndex::class)
            ->set('equipmentId', (string) $equipment->id)
            ->set('startDate', '2026-09-07')
            ->set('endDate', '2026-09-09');

        $component->call('submit')->call('submit');
        $this->assertDatabaseCount('requests', 1);

        $stale = $this->equipment('Available');
        $stale->update(['status' => 'Reserved']);
        Livewire::actingAs($employee)
            ->test(BorrowIndex::class)
            ->set('equipmentId', (string) $stale->id)
            ->set('startDate', '2026-09-07')
            ->set('endDate', '2026-09-09')
            ->call('submit');

        $this->assertDatabaseMissing('requests', ['equipment_id' => $stale->id]);
    }

    private function user(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'name' => "User {$number}",
            'email' => "borrow-user-{$number}@example.test",
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
            'asset_tag' => "BORROW-{$number}",
            'category' => 'Laptop',
            'serial' => "BORROW-SERIAL-{$number}",
            'condition' => 'Good',
            'status' => $status,
        ]);
    }
}
