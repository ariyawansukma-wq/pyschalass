<?php

namespace App\Imports;

use App\Models\Benchmark;
use App\Models\Indicator;
use App\Models\SportBranch;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class BenchmarksImport implements ToCollection
{
    private ?int $fallbackSportBranchId;
    private array $caborCache = [];

    public function __construct(?int $fallbackSportBranchId = null)
    {
        $this->fallbackSportBranchId = $fallbackSportBranchId;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Determine header structure (2-row merged vs 1-row flat)
        $startRow = 1;
        $headers = [];

        $row0 = $rows[0]->toArray();
        $row1 = isset($rows[1]) ? $rows[1]->toArray() : [];

        $isTwoRowHeader = false;
        // Check if Row 1 contains subheadings like L, P, Male, Female
        foreach ($row1 as $cell) {
            $c = strtoupper(trim((string)$cell));
            if ($c === 'L' || $c === 'P' || $c === 'M' || $c === 'F' || $c === 'PRIA' || $c === 'WANITA') {
                $isTwoRowHeader = true;
                break;
            }
        }

        if ($isTwoRowHeader) {
            $startRow = 2;
            $lastTopHeader = '';
            for ($col = 0; $col < count($row0); $col++) {
                $top = trim((string)($row0[$col] ?? ''));
                if ($top !== '') {
                    $lastTopHeader = $top;
                }
                $sub = trim((string)($row1[$col] ?? ''));
                $headers[$col] = trim($lastTopHeader . ' ' . $sub);
            }
        } else {
            // Flat 1-row header
            $startRow = 1;
            for ($col = 0; $col < count($row0); $col++) {
                $headers[$col] = trim((string)($row0[$col] ?? ''));
            }
        }

        // Identify fixed column indexes
        $caborCol = null;
        $categoryCol = null;
        $nameCol = null;
        $unitCol = null;
        $evalCol = null;
        $benchmarkCols = [];

        for ($c = 0; $c < count($headers); $c++) {
            $hLower = strtolower($headers[$c]);
            if (in_array($hLower, ['cabor', 'cabang olahraga', 'sport branch'])) {
                $caborCol = $c;
            } elseif (in_array($hLower, ['komponen', 'category', 'biomotor'])) {
                $categoryCol = $c;
            } elseif (in_array($hLower, ['metode pengukuran', 'nama tes', 'indicator', 'indikator'])) {
                $nameCol = $c;
            } elseif (in_array($hLower, ['satuan', 'unit'])) {
                $unitCol = $c;
            } elseif (in_array($hLower, ['hasil evaluasi', 'evaluation', 'rekomendasi'])) {
                $evalCol = $c;
            } else {
                $benchmarkCols[$c] = $headers[$c];
            }
        }

        // Fallbacks if columns not identified by text
        if ($nameCol === null) {
            // If col 0 = category, col 1 = name, col 2 = unit
            $categoryCol = $categoryCol ?? 0;
            $nameCol = 1;
            $unitCol = $unitCol ?? 2;
            unset($benchmarkCols[0], $benchmarkCols[1], $benchmarkCols[2]);
        }

        // Accumulated benchmark values: [ branch_id => [ bm_key => [ indicator_id => score ] ] ]
        $caborData = [];
        $lastCaborName = '';
        $lastCategory = '';

        for ($r = $startRow; $r < count($rows); $r++) {
            $row = $rows[$r]->toArray();

            $rawCaborName = $caborCol !== null ? trim((string)($row[$caborCol] ?? '')) : '';
            if (!empty($rawCaborName)) {
                $lastCaborName = $rawCaborName;
            }
            $caborName = $lastCaborName;

            $rawCategory = $categoryCol !== null ? trim((string)($row[$categoryCol] ?? '')) : '';
            if (!empty($rawCategory)) {
                $lastCategory = $rawCategory;
            }
            $category = $lastCategory;

            $name = trim((string)($row[$nameCol] ?? ''));
            $unit = $unitCol !== null ? trim((string)($row[$unitCol] ?? '')) : '';
            $eval = $evalCol !== null ? trim((string)($row[$evalCol] ?? '')) : '';

            if (empty($name)) {
                continue;
            }

            // Resolve Sport Branch ID (case-insensitive lookup or auto-creation)
            $branchId = $this->resolveSportBranchId($caborName);
            if (!$branchId) {
                continue;
            }

            // Find or create Indicator under resolved sport branch
            $indicator = Indicator::firstOrNew([
                'sport_branch_id' => $branchId,
                'name' => $name,
            ]);

            if ($category) $indicator->category = $category;
            if ($unit) $indicator->unit = $unit;
            if ($eval) $indicator->evaluation = $eval;
            $indicator->scoring_direction = $indicator->scoring_direction ?? 'HIGHER_IS_BETTER';
            $indicator->save();

            $indId = (string) $indicator->id;

            if (!isset($caborData[$branchId])) {
                $caborData[$branchId] = [];
            }

            // Process benchmark value columns
            foreach ($benchmarkCols as $cIdx => $colHeader) {
                $rawVal = $row[$cIdx] ?? null;
                if ($rawVal === null || $rawVal === '') {
                    continue;
                }

                $numericVal = (float) str_replace(',', '.', (string)$rawVal);
                $bmKey = $this->resolveBenchmarkKey($colHeader);

                if (!isset($caborData[$branchId][$bmKey])) {
                    $caborData[$branchId][$bmKey] = [];
                }
                $caborData[$branchId][$bmKey][$indId] = $numericVal;
            }
        }

        // Save accumulated benchmarks for each sport branch
        foreach ($caborData as $branchId => $benchmarksMap) {
            foreach ($benchmarksMap as $bmKey => $valuesMap) {
                $this->saveBenchmarkValues($branchId, $bmKey, $valuesMap);
            }
        }
    }

    private function resolveSportBranchId(string $caborName): ?int
    {
        if (empty($caborName)) {
            return $this->fallbackSportBranchId;
        }

        $lowerName = mb_strtolower($caborName);

        if (isset($this->caborCache[$lowerName])) {
            return $this->caborCache[$lowerName];
        }

        // Case-insensitive database lookup
        $branch = SportBranch::whereRaw('LOWER(name) = ?', [$lowerName])->first();

        if (!$branch) {
            // Auto-create sport branch if it doesn't exist
            $branch = SportBranch::create(['name' => ucwords($caborName)]);
        }

        $this->caborCache[$lowerName] = $branch->id;
        return $branch->id;
    }

    private function resolveBenchmarkKey(string $headerText): string
    {
        $lower = strtolower($headerText);

        // Detect Gender
        $gender = 'M';
        if (str_contains($lower, 'wanita') || str_contains($lower, 'female') || str_contains($lower, '(p)') || str_ends_with($lower, ' p') || str_contains($lower, ' p ')) {
            $gender = 'F';
        }

        // Detect Age Range
        $ageMin = 1;
        $ageMax = 100;

        if (str_contains($lower, '15') || str_contains($lower, '≤15') || str_contains($lower, '<=15')) {
            $ageMin = 1;
            $ageMax = 15;
        } elseif (str_contains($lower, '16') || str_contains($lower, '≥16') || str_contains($lower, '>=16') || str_contains($lower, 'senior')) {
            $ageMin = 16;
            $ageMax = 100;
        } elseif (preg_match('/(\d+)[\s_\-]+(\d+)/', $lower, $matches)) {
            $ageMin = (int) $matches[1];
            $ageMax = (int) $matches[2];
        }

        return "{$gender}_{$ageMin}_{$ageMax}_{$headerText}";
    }

    private function saveBenchmarkValues(int $branchId, string $bmKey, array $valuesMap): void
    {
        [$gender, $ageMin, $ageMax, $rawHeader] = explode('_', $bmKey, 4);

        $label = match(true) {
            $ageMax <= 15 && $gender === 'M' => 'Standard ≤15 Tahun (M)',
            $ageMax <= 15 && $gender === 'F' => 'Standard ≤15 Tahun (F)',
            $ageMin >= 16 && $gender === 'M' => 'Standard ≥16 Tahun (M)',
            $ageMin >= 16 && $gender === 'F' => 'Standard ≥16 Tahun (F)',
            default => "Standard {$ageMin}-{$ageMax} Tahun (" . ($gender === 'M' ? 'Pria' : 'Wanita') . ")",
        };

        $benchmark = Benchmark::firstOrCreate(
            [
                'sport_branch_id' => $branchId,
                'gender' => $gender,
                'age_min' => (int) $ageMin,
                'age_max' => (int) $ageMax,
            ],
            [
                'label' => $label,
                'values' => [],
            ]
        );

        $existingValues = $benchmark->values ?? [];
        $mergedValues = array_replace($existingValues, $valuesMap);
        $benchmark->update(['values' => $mergedValues]);
    }
}
