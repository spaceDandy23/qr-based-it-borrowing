<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Checked Out', 'Returned', 'Cancelled'])
                ->default('Pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Checked Out', 'Returned'])
                ->default('Pending')
                ->change();
        });
    }
};
