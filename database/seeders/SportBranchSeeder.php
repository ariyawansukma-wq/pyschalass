<?php

namespace Database\Seeders;

use App\Models\SportBranch;
use Illuminate\Database\Seeder;

class SportBranchSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            ['name' => 'Athletics',   'description' => 'Track and field athletics including sprints, jumps, and throws'],
            ['name' => 'Swimming',    'description' => 'Swimming and aquatic sports including freestyle, breaststroke, and butterfly'],
            ['name' => 'Football',    'description' => 'Football / soccer including field players and goalkeepers'],
            ['name' => 'Badminton',   'description' => 'Badminton including singles and doubles disciplines'],
            ['name' => 'Volleyball',  'description' => 'Indoor volleyball including setters, hitters, and liberos'],
        ];

        foreach ($sports as $sport) {
            SportBranch::create($sport);
        }
    }
}
