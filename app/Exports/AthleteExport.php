<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AthleteExport implements FromCollection, WithHeadings, WithStyles, WithMapping, ShouldAutoSize
{
    protected $athlete;
    protected $sessions;
    protected $indicators;
    protected $scoresPerSession;
    protected $overallPerSession;

    public function __construct($athlete, $sessions, $indicators, $scoresPerSession, $overallPerSession)
    {
        $this->athlete = $athlete;
        $this->sessions = $sessions;
        $this->indicators = $indicators;
        $this->scoresPerSession = $scoresPerSession;
        $this->overallPerSession = $overallPerSession;
    }

    public function collection()
    {
        $rows = [];

        foreach ($this->indicators as $indicator) {
            $row = ['indicator' => $indicator->name . ' (' . $indicator->unit . ')'];

            foreach ($this->sessions as $session) {
                $score = $this->scoresPerSession[$session->id][$indicator->id] ?? null;
                $row['session_' . $session->id] = $score !== null ? number_format($score, 1) . '%' : '-';
            }

            $row['average'] = $this->calculateAverage($indicator);
            $rows[] = (object) $row;
        }

        // Add overall score row
        $overallRow = ['indicator' => 'OVERALL SCORE'];
        foreach ($this->sessions as $session) {
            $score = $this->overallPerSession[$session->id] ?? null;
            $overallRow['session_' . $session->id] = $score !== null ? number_format($score, 1) . '%' : '-';
        }
        $overallRow['average'] = $this->calculateOverallAverage();
        $rows[] = (object) $overallRow;

        return collect($rows);
    }

    public function headings(): array
    {
        $headings = ['Indicator'];

        foreach ($this->sessions as $session) {
            $headings[] = $session->name . ' (' . $session->date_time->format('d/m/Y') . ')';
        }

        $headings[] = 'Average';

        return $headings;
    }

    public function map($row): array
    {
        $data = [$row->indicator];

        foreach ($this->sessions as $session) {
            $data[] = $row->{'session_' . $session->id} ?? '-';
        }

        $data[] = $row->average;

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    protected function calculateAverage($indicator)
    {
        $scores = array_filter($this->scoresPerSession, function ($sessionScores) use ($indicator) {
            return isset($sessionScores[$indicator->id]) && $sessionScores[$indicator->id] !== null;
        });

        if (empty($scores)) return '-';

        $values = array_map(function ($sessionScores) use ($indicator) {
            return $sessionScores[$indicator->id];
        }, $scores);

        return number_format(array_sum($values) / count($values), 1) . '%';
    }

    protected function calculateOverallAverage()
    {
        $validScores = array_filter($this->overallPerSession, fn($v) => $v !== null);

        if (empty($validScores)) return '-';

        return number_format(array_sum($validScores) / count($validScores), 1) . '%';
    }
}
