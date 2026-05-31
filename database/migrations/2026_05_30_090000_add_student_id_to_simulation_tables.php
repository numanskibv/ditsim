<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the per-student ownership dimension to every simulation table, so each
 * student works in an isolated world. The column is nullable: null rows form a
 * shared baseline (seed/demo data) visible across worlds.
 */
return new class extends Migration
{
    /**
     * The simulation tables that belong to a student world.
     *
     * @var list<string>
     */
    private array $tables = [
        'devices',
        'tickets',
        'device_alerts',
        'visitor_logs',
        'inspection_reports',
        'messages',
        'installation_plans',
    ];

    public function up(): void
    {
        // Racks are the world container and carry a per-student unique name.
        Schema::table('racks', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable()->after('id')
                ->constrained('users')->cascadeOnDelete();
            $table->dropUnique('racks_name_unique');
            $table->unique(['student_id', 'name']);
        });

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('student_id')->nullable()->after('id')
                    ->constrained('users')->cascadeOnDelete();
                $table->index('student_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['student_id']);
                $table->dropConstrainedForeignId('student_id');
            });
        }

        Schema::table('racks', function (Blueprint $table): void {
            $table->dropUnique(['student_id', 'name']);
            $table->dropConstrainedForeignId('student_id');
            $table->unique('name');
        });
    }
};
