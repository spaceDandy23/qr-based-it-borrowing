<?php

namespace App\Services;

use App\Exceptions\BorrowingStateException;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EquipmentLifecycleService
{
    public const CONDITIONS = ['Excellent', 'Good', 'Fair', 'Poor'];

    public function markMaintenance(int $equipmentId, User $actor): Equipment
    {
        return $this->transition($equipmentId, $actor, ['Available', 'Damaged'], 'Maintenance', 'Maintenance');
    }

    public function markDamaged(int $equipmentId, User $actor): Equipment
    {
        return $this->transition($equipmentId, $actor, ['Available'], 'Damaged', 'Damaged');
    }

    public function returnToService(int $equipmentId, string $condition, User $actor): Equipment
    {
        if (! in_array($condition, self::CONDITIONS, true)) {
            throw new BorrowingStateException('The selected maintenance condition is invalid.');
        }

        return DB::transaction(function () use ($equipmentId, $condition, $actor) {
            $this->ensureAdmin($actor);
            $equipment = Equipment::query()->lockForUpdate()->findOrFail($equipmentId);

            if ($equipment->status !== 'Maintenance') {
                throw new BorrowingStateException('Only equipment under maintenance can be returned to service.');
            }

            $previousCondition = $equipment->condition;
            $equipment->update(['condition' => $condition, 'status' => 'Available']);
            AuditLog::record(
                'Maintenance resolved',
                "{$equipment->name} ({$equipment->asset_tag}) Maintenance -> Available. Condition: {$previousCondition} -> {$condition}.",
                $actor,
            );

            return $equipment->fresh();
        });
    }

    private function transition(int $equipmentId, User $actor, array $from, string $to, string $action): Equipment
    {
        return DB::transaction(function () use ($equipmentId, $actor, $from, $to, $action) {
            $this->ensureAdmin($actor);
            $equipment = Equipment::query()->lockForUpdate()->findOrFail($equipmentId);

            if (! in_array($equipment->status, $from, true)) {
                throw new BorrowingStateException("{$equipment->name} cannot be marked as {$to} from its current status.");
            }

            $previousStatus = $equipment->status;
            $equipment->update(['status' => $to]);
            AuditLog::record(
                $action,
                "{$equipment->name} ({$equipment->asset_tag}) {$previousStatus} -> {$to}.",
                $actor,
            );

            return $equipment->fresh();
        });
    }

    private function ensureAdmin(User $actor): void
    {
        if (! $actor->isAdmin()) {
            throw new BorrowingStateException('Only admins can manage equipment lifecycle status.');
        }
    }
}
