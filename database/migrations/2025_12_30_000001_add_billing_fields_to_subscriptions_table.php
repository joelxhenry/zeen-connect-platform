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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('billing_cycle')->default('monthly')->after('status'); // monthly, annual
            $table->timestamp('trial_ends_at')->nullable()->after('billing_cycle');
            $table->string('powertranz_profile_id')->nullable()->after('stripe_subscription_id'); // Recurring profile ID
            $table->timestamp('cancelled_at')->nullable()->after('expires_at');
            $table->timestamp('grace_period_ends_at')->nullable()->after('cancelled_at');
            $table->boolean('has_used_trial')->default(false)->after('grace_period_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'billing_cycle',
                'trial_ends_at',
                'powertranz_profile_id',
                'cancelled_at',
                'grace_period_ends_at',
                'has_used_trial',
            ]);
        });
    }
};
