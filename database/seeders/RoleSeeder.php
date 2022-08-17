<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert(
            [
                'id' => '56d01e2e-2334-49c0-9469-4419d9cc0a62',
                'name' => 'applicant'
            ]
        );

        DB::table('roles')->insert(
            [
                'id' => '5695fbbd-4675-4b2a-b31d-603252c21c94',
                'name' => 'recruiter'
            ]
        );
    }
}
