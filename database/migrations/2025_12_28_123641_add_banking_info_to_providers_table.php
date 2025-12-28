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
            $table->string('bank_name')->nullable()->after('fee_payer');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_holder_name')->nullable()->after('bank_account_number');
            $table->string('bank_branch_code')->nullable()->after('bank_account_holder_name');
            $table->enum('bank_account_type', ['savings', 'checking'])->nullable()->after('bank_branch_code');
            $table->boolean('banking_info_verified')->default(false)->after('bank_account_type');
            $table->timestamp('banking_info_verified_at')->nullable()->after('banking_info_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_number',
                'bank_account_holder_name',
                'bank_branch_code',
                'bank_account_type',
                'banking_info_verified',
                'banking_info_verified_at',
            ]);
        });
    }
};
