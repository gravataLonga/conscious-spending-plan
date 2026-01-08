<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('net_worths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->decimal('assets', 12, 2)->default(0);
            $table->decimal('invested', 12, 2)->default(0);
            $table->decimal('saving', 12, 2)->default(0);
            $table->decimal('debt', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['plan_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('net_worths');
    }
};
