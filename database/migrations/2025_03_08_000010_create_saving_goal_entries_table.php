<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_goal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_goal_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['saving_goal_category_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_goal_entries');
    }
};
