<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('companies')->insert(
            [
                'id' => 'eedc1657-148f-42ba-8e0a-f1c20c6f4454',
                'user_id' => '11323c85-5633-4d1a-bc40-de19c7c77a23',
                'name' => 'GeeksHubs',
                'address' => 'Coworking Wayco Ruzafa',
                'email' => 'geekshubs@gmail.com',
                'description' => 'entity: BETALAB INNOVATION S.L',
                'status' => 'active'
            ]
        );
    }
}
