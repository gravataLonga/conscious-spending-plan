<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->decimal('net', 12, 2)->default(0);
            $table->decimal('gross', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['plan_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
