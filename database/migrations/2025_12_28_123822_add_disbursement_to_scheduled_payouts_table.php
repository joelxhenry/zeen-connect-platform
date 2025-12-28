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
        Schema::table('scheduled_payouts', function (Blueprint $table) {
            $table->string('disbursement_id')->nullable()->after('reference_number');
            $table->json('disbursement_response')->nullable()->after('disbursement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_payouts', function (Blueprint $table) {
            $table->dropColumn(['disbursement_id', 'disbursement_response']);
        });
    }
};
