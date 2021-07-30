<?php

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrenciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Currency::truncate();
        Currency::insert([
            [
                'type'                   => 'fiat',
                'name'                   => 'US Dollar',
                'symbol'                 => '$',
                'code'                   => 'USD',
                'rate'                   => '1',
                'logo'                   => 'icons8-us-dollar-64.png',
                'exchange_from'          => 'local',
                'default'                => '1',
                'allow_address_creation' => 'No',
                'status'                 => 'Active',
            ],
            [
                'type'                   => 'fiat',
                'name'                   => 'Pound Sterling',
                'symbol'                 => '£',
                'code'                   => 'GBP',
                'rate'                   => '0.75',
                'logo'                   => 'icons8-british-pound-64.png',
                'exchange_from'          => 'api',
                'default'                => '0',
                'allow_address_creation' => 'No',
                'status'                 => 'Active',
            ],
            [
                'type'                   => 'fiat',
                'name'                   => 'Europe',
                'symbol'                 => '€',
                'code'                   => 'EUR',
                'rate'                   => '0.85',
                'logo'                   => 'icons8-euro-64.png',
                'exchange_from'          => 'local',
                'default'                => '0',
                'allow_address_creation' => 'No',
                'status'                 => 'Active',
            ],
            [
                'type'                   => 'fiat',
                'name'                   => 'Indian Rupee',
                'symbol'                 => '₡',
                'code'                   => 'INR',
                'rate'                   => '71.82',
                'logo'                   => 'icons8-rupee-64.png',
                'exchange_from'          => 'local',
                'default'                => '0',
                'allow_address_creation' => 'No',
                'status'                 => 'Active',
            ],
        ]);
    }
}
