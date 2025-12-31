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
        Schema::create('event_recurrence_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();

            $table->enum('frequency', ['weekly'])->default('weekly');
            $table->unsignedTinyInteger('interval')->default(1);
            $table->json('days_of_week')->nullable();
            $table->time('time_of_day');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();

            $table->timestamps();

            $table->index('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_recurrence_rules');
    }
};
