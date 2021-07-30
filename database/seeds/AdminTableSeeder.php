<?php

use Illuminate\Database\Seeder;

class AdminTableSeeder extends Seeder
{
    public function run()
    {
        \DB::table('admins')->insert([
            [
                'id'         => 2,
                'role_id'    => 1,
                'first_name' => 'admin',
                'last_name'  => 'trans',
                'email'      => 'admin@example.net',
                'password'   => \Hash::make('123456'),
                'status'     => 'Active',
            ],
        ]);
    }
}
