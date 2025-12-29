<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GatewaySeeder extends Seeder
{
    /**
     * Seed gateways using updateOrInsert for idempotency.
     * Only WiPay is active; deprecated gateways are removed.
     */
    public function run(): void
    {
        $gateways = $this->getGateways();
        $now = now();

        foreach ($gateways as $gateway) {
            DB::table('gateways')->updateOrInsert(
                ['slug' => $gateway['slug']],
                [
                    'name' => $gateway['name'],
                    'type' => $gateway['type'],
                    'is_active' => $gateway['is_active'],
                    'supports_split' => $gateway['supports_split'],
                    'supports_escrow' => $gateway['supports_escrow'],
                    'supported_currencies' => json_encode($gateway['supported_currencies']),
                    'updated_at' => $now,
                ]
            );
        }

        // Remove deprecated gateways
        DB::table('gateways')
            ->whereIn('slug', ['powertranz', 'fygaro'])
            ->delete();

        $this->command->info('Gateways seeded: '.count($gateways).' active gateways');
    }

    private function getGateways(): array
    {
        return [
            [
                'name' => 'WiPay',
                'slug' => 'wipay',
                'type' => 'escrow',
                'is_active' => true,
                'supports_split' => true,
                'supports_escrow' => true,
                'supported_currencies' => ['JMD', 'TTD', 'USD'],
            ],
        ];
    }
}
