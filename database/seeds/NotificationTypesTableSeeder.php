<?php

use App\Models\NotificationType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NotificationTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        NotificationType::truncate();
        NotificationType::insert([
            [
                'id'         => '1',
                'name'       => 'Deposit',
                'alias'      => 'deposit',
                'status'     => 'Active',
            ],

            [
                'id'         => '2',
                'name'       => 'Payout',
                'alias'      => 'payout',
                'status'     => 'Active',
            ],

            [
                'id'         => '3',
                'name'       => 'Send',
                'alias'      => 'send',
                'status'     => 'Active',
            ],

            [
                'id'         => '4',
                'name'       => 'Request',
                'alias'      => 'request',
                'status'     => 'Active',
            ],

            [
                'id'         => '5',
                'name'       => 'Exchange',
                'alias'      => 'exchange',
                'status'     => 'Active',
            ],

            [
                'id'         => '6',
                'name'       => 'Payment',
                'alias'      => 'payment',
                'status'     => 'Active',
            ],
        ]);
    }
}
