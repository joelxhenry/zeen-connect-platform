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
        Schema::table('payments', function (Blueprint $table) {
            // Gateway strategy tracking
            $table->string('gateway_type')->default('escrow')->after('gateway'); // split, escrow
            $table->string('gateway_provider')->nullable()->after('gateway_type'); // wipay, fygaro, powertranz

            // Split payment tracking
            $table->string('split_transaction_id')->nullable()->after('gateway_transaction_id');
            $table->json('split_details')->nullable()->after('split_transaction_id');

            // Ledger reference
            $table->foreignId('ledger_entry_id')->nullable()->after('split_details')
                ->constrained('ledger_entries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['ledger_entry_id']);
            $table->dropColumn([
                'gateway_type',
                'gateway_provider',
                'split_transaction_id',
                'split_details',
                'ledger_entry_id',
            ]);
        });
    }
};
