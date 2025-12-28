<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete provider gateway configs for powertranz and fygaro
        DB::table('provider_gateway_configs')
            ->whereIn('gateway_id', function ($query) {
                $query->select('id')
                    ->from('gateways')
                    ->whereIn('slug', ['powertranz', 'fygaro']);
            })
            ->delete();

        // Delete powertranz and fygaro from gateways table
        DB::table('gateways')
            ->whereIn('slug', ['powertranz', 'fygaro'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add powertranz and fygaro gateways
        DB::table('gateways')->insert([
            [
                'name' => 'PowerTranz',
                'slug' => 'powertranz',
                'type' => 'escrow',
                'is_active' => true,
                'supports_split' => false,
                'supports_escrow' => true,
                'supported_currencies' => json_encode(['JMD', 'USD', 'TTD', 'BBD', 'XCD']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fygaro',
                'slug' => 'fygaro',
                'type' => 'escrow',
                'is_active' => true,
                'supports_split' => true,
                'supports_escrow' => true,
                'supported_currencies' => json_encode(['JMD', 'USD']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
};
