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
        Schema::table('plan_snapshots', function (Blueprint $table) {
            $table->foreignId('snapshot_plan_id')
                ->nullable()
                ->constrained('plans')
                ->cascadeOnDelete()
                ->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_snapshots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('snapshot_plan_id');
        });
    }
};
