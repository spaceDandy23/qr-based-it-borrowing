<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();
            $table->string('name');
            $table->enum('category', [
                'Laptop', 'Desktop', 'Monitor', 'Projector', 'Camera',
                'Networking', 'Printer', 'Peripheral', 'Tablet', 'Audio',
            ]);
            $table->string('serial')->unique();
            $table->enum('condition', ['Excellent', 'Good', 'Fair', 'Poor'])->default('Good');
            $table->enum('status', ['Available', 'Checked Out', 'Reserved', 'Maintenance', 'Damaged'])
                ->default('Available');
            $table->string('location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
