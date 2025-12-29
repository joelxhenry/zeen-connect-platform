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
        Schema::table('providers', function (Blueprint $table) {
            $table->unsignedSmallInteger('buffer_minutes')->default(0)->after('fee_payer');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unsignedSmallInteger('buffer_minutes')->nullable()->after('max_advance_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('buffer_minutes');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('buffer_minutes');
        });
    }
};
