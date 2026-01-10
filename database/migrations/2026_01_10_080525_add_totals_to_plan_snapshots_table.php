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
            $table->decimal('total_net_worth', 12, 2)->nullable()->after('captured_at');
            $table->decimal('net_income', 12, 2)->nullable()->after('total_net_worth');
            $table->decimal('total_expenses', 12, 2)->nullable()->after('net_income');
            $table->decimal('total_saving', 12, 2)->nullable()->after('total_expenses');
            $table->decimal('total_investing', 12, 2)->nullable()->after('total_saving');
            $table->decimal('guilt_free', 12, 2)->nullable()->after('total_investing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'total_net_worth',
                'net_income',
                'total_expenses',
                'total_saving',
                'total_investing',
                'guilt_free',
            ]);
        });
    }
};
