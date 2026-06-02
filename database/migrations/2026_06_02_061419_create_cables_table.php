<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->nullable()->index();

            // The number/label the student assigns to the cable (e.g. K-001).
            $table->string('label');
            $table->string('medium')->default('utp');
            $table->string('color')->nullable();

            // Both endpoints: a port on a device (or patch panel).
            $table->foreignId('from_device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedSmallInteger('from_port');
            $table->foreignId('to_device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedSmallInteger('to_port');

            $table->timestamp('last_changed_at')->nullable();
            $table->foreignId('last_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // A cable number is unique within a student's own world.
            $table->unique(['student_id', 'label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cables');
    }
};
