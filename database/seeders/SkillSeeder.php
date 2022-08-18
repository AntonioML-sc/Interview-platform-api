<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('skills')->insert(
            [
                'id' => 'f6fcd91d-ed2c-43fd-b7e7-5aa56c9e3948',
                'title' => 'Javascript',
                'description' => 'JavaScript is a high-level, often just-in-time compiled language that conforms to the ECMAScript standard.'
            ]
        );

        DB::table('skills')->insert(
            [
                'id' => '9bb20f44-6455-4e64-aa8c-dd783bfae88c',
                'title' => 'PHP',
                'description' => 'PHP is a general-purpose scripting language geared toward web development.'
            ]
        );

        DB::table('skills')->insert(
            [
                'id' => '99783f19-52a7-4cec-8180-8a728fd4bba3',
                'title' => 'HTML',
                'description' => 'The HyperText Markup Language or HTML is the standard markup language for documents designed to be displayed in a web browser.'
            ]
        );

        DB::table('skills')->insert(
            [
                'id' => 'a3c06730-7018-467d-8187-cef95f37224d',
                'title' => 'CSS',
                'description' => 'Cascading Style Sheets (CSS) is a style sheet language used for describing the presentation of a document written in a markup language such as HTML or XML.'
            ]
        );

        DB::table('skills')->insert(
            [
                'id' => '56d01e2e-2334-49c0-9469-4419d9cc0a62',
                'title' => 'GIT',
                'description' => 'Git is free and open source software for distributed version control.'
            ]
        );

        DB::table('skills')->insert(
            [
                'id' => '5695fbbd-4675-4b2a-b31d-603252c21c94',
                'title' => 'Docker',
                'description' => 'Docker is a set of platform as a service (PaaS) products that use OS-level virtualization to deliver software in packages called containers.'
            ]
        );

        DB::table('skills')->insert(
            [
                'id' => '102cae9e-bccf-4530-ae64-054fa56e73c6',
                'title' => 'SQL',
                'description' => 'SQL (Structured Query Language) is a domain-specific language used in programming and designed for managing data held in a relational database management system.'
            ]
        );

        DB::table('skills')->insert(
            [
                'id' => '28afaf75-7105-4f83-b34c-59bf7ff7ddb3',
                'title' => 'Java',
                'description' => 'Java is a high-level, class-based, object-oriented, general-purpose programming language.'
            ]
        );
    }
}
