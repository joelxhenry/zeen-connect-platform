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
        Schema::create('video_embeds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('model'); // model_type, model_id
            $table->string('platform'); // youtube, vimeo
            $table->string('video_id');
            $table->string('url');
            $table->text('embed_code')->nullable();
            $table->string('title')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedInteger('duration')->nullable(); // seconds
            $table->json('metadata')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            // Note: morphs() already creates an index on model_type and model_id
            $table->index(['platform', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_embeds');
    }
};
