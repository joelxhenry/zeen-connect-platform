<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add domain column as nullable initially
        Schema::table('providers', function (Blueprint $table) {
            $table->string('domain')->nullable()->unique()->after('slug');
        });

        // Backfill existing providers: copy slug to domain
        DB::table('providers')->whereNull('domain')->update([
            'domain' => DB::raw('slug'),
        ]);

        // Make domain non-nullable after backfill
        Schema::table('providers', function (Blueprint $table) {
            $table->string('domain')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('domain');
        });
    }
};
