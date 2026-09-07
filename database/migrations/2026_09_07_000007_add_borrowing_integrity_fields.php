<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->text('return_notes')->nullable()->after('return_condition');
            $table->index(['equipment_id', 'status']);
            $table->index(['user_id', 'equipment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['equipment_id', 'status']);
            $table->dropIndex(['user_id', 'equipment_id', 'status']);
            $table->dropColumn('return_notes');
        });
    }
};
