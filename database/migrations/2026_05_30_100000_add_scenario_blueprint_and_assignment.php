<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the provisioning layer: a scenario can carry a `blueprint` describing the
 * starting world (rack + devices), and each student records which scenario is
 * currently assigned to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table): void {
            $table->json('blueprint')->nullable()->after('actions');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('assigned_scenario_id')->nullable()->after('role')
                ->constrained('scenarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_scenario_id');
        });

        Schema::table('scenarios', function (Blueprint $table): void {
            $table->dropColumn('blueprint');
        });
    }
};
