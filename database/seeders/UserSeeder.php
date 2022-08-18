<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert(
            [
                'id' => '11323c85-5633-4d1a-bc40-de19c7c77a23',
                'role_id' => '5695fbbd-4675-4b2a-b31d-603252c21c94',
                'last_name' => 'Morales Ledesma',
                'first_name' => 'Antonio',
                'email' => 'antonio@gmail.com',
                'password' => bcrypt('1234AaB!'),
                'phone' => '+34 666 555 444',
                'title' => 'Recruiter',
                'description' => 'Hiring',
                'status' => 'active'
            ]
        );

        DB::table('users')->insert(
            [
                'id' => '5d115577-f4e0-4e27-ab8b-a5f2ea99da3b',
                'role_id' => '56d01e2e-2334-49c0-9469-4419d9cc0a62',
                'last_name' => 'García García',
                'first_name' => 'Ana',
                'email' => 'ana@gmail.com',
                'password' => bcrypt('1234AaB!'),
                'phone' => '+34 777 555 444',
                'title' => 'Software developer',
                'description' => 'Interested in science and technology and open to work',
                'status' => 'active'
            ]
        );

        DB::table('users')->insert(
            [
                'id' => '99783f19-52a7-4cec-8180-8a728fd4bba3',
                'role_id' => '56d01e2e-2334-49c0-9469-4419d9cc0a62',
                'last_name' => 'Smith',
                'first_name' => 'John',
                'email' => 'john@gmail.com',
                'password' => bcrypt('1234AaB!'),
                'phone' => '+44 666 888 222',
                'title' => 'Software developer',
                'description' => 'Interested in science and technology and open to work',
                'status' => 'active'
            ]
        );
    }
}
