<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComparisonExport implements FromCollection, WithHeadings, WithStyles, WithMapping, ShouldAutoSize
{
    protected $comparedAthletes;
    protected $indicators;
    protected $columns;
    protected $comparisonMatrix;
    protected $rawMatrix;
    protected $overallPerColumn;

    public function __construct($comparedAthletes, $indicators, $columns, $comparisonMatrix, $rawMatrix, $overallPerColumn)
    {
        $this->comparedAthletes = $comparedAthletes;
        $this->indicators = $indicators;
        $this->columns = $columns;
        $this->comparisonMatrix = $comparisonMatrix;
        $this->rawMatrix = $rawMatrix;
        $this->overallPerColumn = $overallPerColumn;
    }

    public function collection()
    {
        $rows = [];

        // 1. Overall Score Row
        $overallRow = ['indicator' => 'OVERALL SCORE'];
        foreach ($this->columns as $col) {
            $score = $this->overallPerColumn[$col['key']] ?? null;
            $overallRow[$col['key']] = $score !== null ? number_format($score, 1) . '%' : '-';
        }
        $rows[] = (object) $overallRow;

        // 2. Indicator Rows
        foreach ($this->indicators as $indicator) {
            $row = ['indicator' => $indicator->name . ($indicator->unit ? ' (' . $indicator->unit . ')' : '')];

            foreach ($this->columns as $col) {
                $score = $this->comparisonMatrix[$indicator->id][$col['key']] ?? null;
                $raw = $this->rawMatrix[$indicator->id][$col['key']] ?? null;

                if ($score !== null) {
                    $formatted = number_format($score, 1) . '%';
                    if ($raw !== null) {
                        $formatted .= ' (' . $raw . ')';
                    }
                    $row[$col['key']] = $formatted;
                } else {
                    $row[$col['key']] = '-';
                }
            }

            $rows[] = (object) $row;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $headings = ['Indicator'];

        foreach ($this->columns as $col) {
            $athleteName = $col['athlete']->name;
            $sessionInfo = $col['session']
                ? $col['session']->name . ($col['session']->folder ? ' - ' . $col['session']->folder->name : '')
                : 'No Session';
            $headings[] = "{$athleteName} ({$sessionInfo})";
        }

        return $headings;
    }

    public function map($row): array
    {
        $data = [$row->indicator];

        foreach ($this->columns as $col) {
            $data[] = $row->{$col['key']} ?? '-';
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true]], // Overall score row is bold
        ];
    }
}
