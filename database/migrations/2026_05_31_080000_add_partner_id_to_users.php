<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Couples two students. Within a couple (X, Y) each student acts as the
 * leidinggevende/klant counter-role in the other's world. The relation is
 * mutual: X.partner_id = Y and Y.partner_id = X.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('partner_id')->nullable()->after('assigned_scenario_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('partner_id');
        });
    }
};
