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
        Schema::create('installation_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('concept');

            // The five mandatory sections.
            $table->text('werkzaamheden')->nullable();
            $table->text('materialen')->nullable();
            $table->text('middelen')->nullable();
            $table->text('betrokken_collega')->nullable();
            $table->text('security_fysiek')->nullable();
            $table->text('security_virtueel')->nullable();

            $table->timestamp('ready_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_plans');
    }
};
