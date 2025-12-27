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
        // Check if we have the old column name or the new one
        $columns = Schema::getColumnListing('providers');

        if (in_array('processing_fee_payer', $columns)) {
            // Old column exists, update records and recreate with new name
            DB::table('providers')
                ->where('processing_fee_payer', 'client')
                ->update(['processing_fee_payer' => 'provider']);

            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('processing_fee_payer');
            });

            Schema::table('providers', function (Blueprint $table) {
                $table->enum('fee_payer', ['provider', 'client'])->default('provider')->after('min_booking_notice_hours');
            });
        } elseif (in_array('fee_payer', $columns)) {
            // New column already exists (from partial migration), just update values
            DB::table('providers')
                ->where('fee_payer', 'client')
                ->update(['fee_payer' => 'provider']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = Schema::getColumnListing('providers');

        if (in_array('fee_payer', $columns)) {
            DB::table('providers')
                ->where('fee_payer', 'provider')
                ->update(['fee_payer' => 'client']);

            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('fee_payer');
            });

            Schema::table('providers', function (Blueprint $table) {
                $table->enum('processing_fee_payer', ['client', 'provider'])->default('client')->after('min_booking_notice_hours');
            });
        }
    }
};
