<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'itsupport@fdcp.gov.ph')],
            [
                'name' => 'IT Support',
                'password' => Hash::make(env('ADMIN_PASSWORD', Str::random(20))),
                'role' => 'admin',
            ]
        );

        collect([
            ['FDCP-HD-001', 'HDMI Cable (2m)', 'Peripheral', 'HDMI-0001', 'Good', 'Available', 'Storage Room A', '2024-01-10'],
            ['FDCP-HD-002', 'HDMI Cable (2m)', 'Peripheral', 'HDMI-0002', 'Good', 'Available', 'Storage Room A', '2024-01-10'],
            ['FDCP-NW-001', 'Broadband Router', 'Networking', 'BB-RTR-0001', 'Good', 'Available', 'Server Room', '2024-01-10'],
            ['FDCP-NW-002', 'Broadband Router', 'Networking', 'BB-RTR-0002', 'Good', 'Available', 'Server Room', '2024-01-10'],
        ])->each(function ($row) {
            [$tag, $name, $category, $serial, $condition, $status, $location, $purchaseDate] = $row;

            Equipment::create([
                'asset_tag' => $tag,
                'name' => $name,
                'category' => $category,
                'serial' => $serial,
                'condition' => $condition,
                'status' => $status,
                'location' => $location,
                'purchase_date' => $purchaseDate,
            ]);
        });

        AuditLog::create([
            'actor_id' => null,
            'actor_name' => 'System',
            'action' => 'Seed',
            'detail' => 'Initialized starting inventory.',
            'created_at' => now(),
        ]);
    }
}
