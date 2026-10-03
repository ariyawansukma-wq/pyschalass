<?php

namespace App\Exports;

use App\Models\Athlete;
use App\Models\Folder;
use App\Services\DashboardService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use Throwable;

class FolderExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?Folder $folder;
    protected $athletesCollection;

    public function __construct(?Folder $folder = null, $athletesCollection = null)
    {
        $this->folder = $folder;
        $this->athletesCollection = $athletesCollection;
    }

    public function collection()
    {
        if ($this->athletesCollection) {
            $athletes = $this->athletesCollection;
        } else {
            $query = Athlete::with(['sportBranch', 'folders']);

            if ($this->folder) {
                $query->whereHas('folders', fn($q) => $q->where('folders.id', $this->folder->id));
            }

            $athletes = $query->orderBy('name')->get();
        }

        $dashboardService = new DashboardService();
        $athleteScores = $dashboardService->getAthleteScores($this->folder, $this->athletesCollection ? $athletes : null);
        $scoresByAthleteId = collect($athleteScores)->keyBy(fn($item) => $item['athlete']['id'] ?? ($item['athlete']->id ?? 0));

        $rows = [];
        foreach ($athletes as $index => $athlete) {
            $scoreData = $scoresByAthleteId->get($athlete->id);

            $overallScore = (isset($scoreData['overall_score']) && $scoreData['overall_score'] !== null)
                ? number_format($scoreData['overall_score'], 1) . '%'
                : '-';
            $bmiCat = $scoreData['bmi_category'] ?? '-';
            $age = $scoreData['age'] ?? ($athlete->date_of_birth ? Carbon::parse($athlete->date_of_birth)->age : '-');

            $rows[] = (object) [
                'no' => $index + 1,
                'name' => $athlete->name,
                'sport_branch' => $athlete->sportBranch->name ?? '-',
                'folder' => $athlete->folders->pluck('name')->join(', ') ?: 'No Folder',
                'gender' => $athlete->gender === 'M' ? 'Male' : 'Female',
                'age' => $age,
                'overall_score' => $overallScore,
                'bmi_category' => $bmiCat,
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Cabor',
            'Folder',
            'Gender',
            'Usia (Tahun)',
            'Skor Performance',
            'Kategori BMI',
        ];
    }

    public function map($row): array
    {
        return [
            $row->no,
            $row->name,
            $row->sport_branch,
            $row->folder,
            $row->gender,
            $row->age,
            $row->overall_score,
            $row->bmi_category,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0B2545'],
                ],
            ],
        ];
    }
}
