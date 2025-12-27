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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('model'); // model_type, model_id
            $table->string('collection')->default('default');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->json('conversions')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'collection']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
