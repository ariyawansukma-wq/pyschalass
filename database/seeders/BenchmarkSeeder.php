<?php

namespace Database\Seeders;

use App\Models\Benchmark;
use App\Models\Indicator;
use App\Models\SportBranch;
use Illuminate\Database\Seeder;

class BenchmarkSeeder extends Seeder
{
    public function run(): void
    {
        $branches = SportBranch::all()->keyBy('name');

        $benchmarks = [
            'Athletics' => [
                // Male
                ['M', 'U-15', 12, 14, ['100m Sprint' => 13.0, 'Long Jump' => 420, 'Javelin Throw' => 35, '400m Run' => 65]],
                ['M', 'U-18', 15, 17, ['100m Sprint' => 12.0, 'Long Jump' => 480, 'Javelin Throw' => 42, '400m Run' => 60]],
                ['M', 'U-21', 18, 21, ['100m Sprint' => 11.2, 'Long Jump' => 530, 'Javelin Throw' => 50, '400m Run' => 54]],
                ['M', 'Senior', 22, 30, ['100m Sprint' => 10.8, 'Long Jump' => 570, 'Javelin Throw' => 58, '400m Run' => 50]],
                // Female
                ['F', 'U-15', 12, 14, ['100m Sprint' => 14.0, 'Long Jump' => 380, 'Javelin Throw' => 28, '400m Run' => 72]],
                ['F', 'U-18', 15, 17, ['100m Sprint' => 13.5, 'Long Jump' => 400, 'Javelin Throw' => 32, '400m Run' => 68]],
                ['F', 'U-21', 18, 21, ['100m Sprint' => 12.8, 'Long Jump' => 440, 'Javelin Throw' => 38, '400m Run' => 62]],
                ['F', 'Senior', 22, 30, ['100m Sprint' => 12.2, 'Long Jump' => 480, 'Javelin Throw' => 42, '400m Run' => 58]],
            ],
            'Swimming' => [
                // Male
                ['M', 'U-15', 12, 14, ['50m Freestyle' => 32, '100m Breaststroke' => 85, '200m Butterfly' => 170]],
                ['M', 'U-18', 15, 17, ['50m Freestyle' => 28, '100m Breaststroke' => 75, '200m Butterfly' => 150]],
                ['M', 'U-21', 18, 21, ['50m Freestyle' => 25, '100m Breaststroke' => 68, '200m Butterfly' => 138]],
                ['M', 'Senior', 22, 30, ['50m Freestyle' => 23, '100m Breaststroke' => 64, '200m Butterfly' => 130]],
                // Female
                ['F', 'U-15', 12, 14, ['50m Freestyle' => 36, '100m Breaststroke' => 92, '200m Butterfly' => 185]],
                ['F', 'U-18', 15, 17, ['50m Freestyle' => 32, '100m Breaststroke' => 84, '200m Butterfly' => 168]],
                ['F', 'U-21', 18, 21, ['50m Freestyle' => 29, '100m Breaststroke' => 76, '200m Butterfly' => 152]],
                ['F', 'Senior', 22, 30, ['50m Freestyle' => 27, '100m Breaststroke' => 72, '200m Butterfly' => 145]],
            ],
            'Football' => [
                // Male
                ['M', 'U-15', 12, 14, ['30m Sprint' => 5.2, 'Shuttle Run' => 13.0, 'Vertical Jump' => 42, 'Push-up' => 25]],
                ['M', 'U-18', 15, 17, ['30m Sprint' => 4.6, 'Shuttle Run' => 11.5, 'Vertical Jump' => 50, 'Push-up' => 35]],
                ['M', 'U-21', 18, 21, ['30m Sprint' => 4.2, 'Shuttle Run' => 10.8, 'Vertical Jump' => 56, 'Push-up' => 42]],
                ['M', 'Senior', 22, 30, ['30m Sprint' => 4.0, 'Shuttle Run' => 10.2, 'Vertical Jump' => 60, 'Push-up' => 48]],
                // Female
                ['F', 'U-15', 12, 14, ['30m Sprint' => 5.8, 'Shuttle Run' => 14.0, 'Vertical Jump' => 35, 'Push-up' => 18]],
                ['F', 'U-18', 15, 17, ['30m Sprint' => 5.4, 'Shuttle Run' => 13.0, 'Vertical Jump' => 40, 'Push-up' => 22]],
                ['F', 'Senior', 22, 30, ['30m Sprint' => 5.0, 'Shuttle Run' => 12.0, 'Vertical Jump' => 45, 'Push-up' => 28]],
            ],
            'Badminton' => [
                // Male
                ['M', 'U-15', 12, 14, ['100m Sprint' => 13.5, 'Sit-up' => 28, 'Vertical Jump' => 42]],
                ['M', 'U-18', 15, 17, ['100m Sprint' => 12.5, 'Sit-up' => 35, 'Vertical Jump' => 50]],
                ['M', 'U-21', 18, 21, ['100m Sprint' => 11.8, 'Sit-up' => 42, 'Vertical Jump' => 56]],
                ['M', 'Senior', 22, 30, ['100m Sprint' => 11.2, 'Sit-up' => 48, 'Vertical Jump' => 60]],
                // Female
                ['F', 'U-15', 12, 14, ['100m Sprint' => 14.5, 'Sit-up' => 22, 'Vertical Jump' => 36]],
                ['F', 'U-18', 15, 17, ['100m Sprint' => 13.8, 'Sit-up' => 28, 'Vertical Jump' => 42]],
                ['F', 'U-21', 18, 21, ['100m Sprint' => 13.2, 'Sit-up' => 34, 'Vertical Jump' => 48]],
                ['F', 'Senior', 22, 30, ['100m Sprint' => 12.8, 'Sit-up' => 40, 'Vertical Jump' => 52]],
            ],
            'Volleyball' => [
                // Male
                ['M', 'U-15', 12, 14, ['30m Sprint' => 5.0, 'Vertical Jump' => 48, 'Push-up' => 22, 'Sit-up' => 26, '12-Minute Run' => 2000]],
                ['M', 'U-18', 15, 17, ['30m Sprint' => 4.5, 'Vertical Jump' => 56, 'Push-up' => 30, 'Sit-up' => 34, '12-Minute Run' => 2400]],
                ['M', 'U-21', 18, 21, ['30m Sprint' => 4.1, 'Vertical Jump' => 62, 'Push-up' => 38, 'Sit-up' => 42, '12-Minute Run' => 2700]],
                ['M', 'Senior', 22, 30, ['30m Sprint' => 3.8, 'Vertical Jump' => 68, 'Push-up' => 44, 'Sit-up' => 48, '12-Minute Run' => 3000]],
                // Female
                ['F', 'U-15', 12, 14, ['30m Sprint' => 5.6, 'Vertical Jump' => 38, 'Push-up' => 15, 'Sit-up' => 20, '12-Minute Run' => 1600]],
                ['F', 'U-18', 15, 17, ['30m Sprint' => 5.2, 'Vertical Jump' => 44, 'Push-up' => 20, 'Sit-up' => 26, '12-Minute Run' => 1900]],
                ['F', 'U-21', 18, 21, ['30m Sprint' => 4.8, 'Vertical Jump' => 50, 'Push-up' => 26, 'Sit-up' => 32, '12-Minute Run' => 2200]],
                ['F', 'Senior', 22, 30, ['30m Sprint' => 4.5, 'Vertical Jump' => 55, 'Push-up' => 30, 'Sit-up' => 38, '12-Minute Run' => 2500]],
            ],
        ];

        foreach ($benchmarks as $sportName => $sets) {
            $branch = $branches[$sportName] ?? null;
            if (!$branch) continue;
            $indicators = Indicator::where('sport_branch_id', $branch->id)->get()->keyBy('name');

            foreach ($sets as [$gender, $label, $ageMin, $ageMax, $rawValues]) {
                $values = [];
                foreach ($rawValues as $indName => $val) {
                    $ind = $indicators[$indName] ?? null;
                    if ($ind) $values[$ind->id] = $val;
                }

                Benchmark::create([
                    'sport_branch_id' => $branch->id,
                    'gender' => $gender,
                    'label' => $label,
                    'age_min' => $ageMin,
                    'age_max' => $ageMax,
                    'values' => $values,
                ]);
            }
        }
    }
}
