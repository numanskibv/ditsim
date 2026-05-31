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
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedTinyInteger('cpu')->default(20)->after('status');
            $table->unsignedSmallInteger('temp')->default(35)->after('cpu');
            $table->unsignedSmallInteger('metric_trend')->default(0)->after('temp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['cpu', 'temp', 'metric_trend']);
        });
    }
};
