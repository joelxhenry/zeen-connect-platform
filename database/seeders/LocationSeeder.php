<?php

namespace Database\Seeders;

use App\Domains\Location\Models\Country;
use App\Domains\Location\Models\Location;
use App\Domains\Location\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Jamaica
        $jamaica = Country::create([
            'uuid' => Str::uuid(),
            'name' => 'Jamaica',
            'code' => 'JM',
            'currency_code' => 'JMD',
            'timezone' => 'America/Jamaica',
            'phone_code' => '+1876',
            'is_active' => true,
        ]);

        // Jamaica Parishes (Regions) and their major towns/cities (Locations)
        $parishes = [
            'Kingston' => ['Kingston', 'Downtown Kingston', 'Uptown Kingston'],
            'St. Andrew' => ['Half Way Tree', 'New Kingston', 'Papine', 'Constant Spring', 'Liguanea', 'Manor Park', 'Stony Hill'],
            'St. Thomas' => ['Morant Bay', 'Port Morant', 'Yallahs'],
            'Portland' => ['Port Antonio', 'Buff Bay', 'Hope Bay'],
            'St. Mary' => ['Port Maria', 'Annotto Bay', 'Oracabessa', 'Highgate'],
            'St. Ann' => ['St. Ann\'s Bay', 'Ocho Rios', 'Brown\'s Town', 'Runaway Bay'],
            'Trelawny' => ['Falmouth', 'Clark\'s Town', 'Duncans'],
            'St. James' => ['Montego Bay', 'Cambridge', 'Anchovy', 'Rose Hall'],
            'Hanover' => ['Lucea', 'Sandy Bay', 'Green Island'],
            'Westmoreland' => ['Savanna-la-Mar', 'Negril', 'Whitehouse', 'Bluefields'],
            'St. Elizabeth' => ['Black River', 'Santa Cruz', 'Junction', 'Treasure Beach'],
            'Manchester' => ['Mandeville', 'Christiana', 'Porus'],
            'Clarendon' => ['May Pen', 'Chapelton', 'Lionel Town', 'Hayes'],
            'St. Catherine' => ['Spanish Town', 'Portmore', 'Old Harbour', 'Linstead', 'Bog Walk'],
        ];

        foreach ($parishes as $parishName => $towns) {
            $region = Region::create([
                'uuid' => Str::uuid(),
                'country_id' => $jamaica->id,
                'name' => $parishName,
                'slug' => Str::slug($parishName),
                'is_active' => true,
            ]);

            foreach ($towns as $townName) {
                Location::create([
                    'uuid' => Str::uuid(),
                    'region_id' => $region->id,
                    'name' => $townName,
                    'slug' => Str::slug($townName),
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Jamaica locations seeded: 14 parishes, ' . array_sum(array_map('count', $parishes)) . ' towns/cities');
    }
}
