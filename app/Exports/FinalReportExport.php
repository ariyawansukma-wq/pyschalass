<?php

namespace App\Exports;

use App\Models\Benchmark;
use App\Models\Indicator;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinalReportExport implements WithMultipleSheets
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Summary'; }
                public function headings(): array { return ['Metric', 'Value']; }
                public function array(): array {
                    return [
                        ['Test Activity Name', $this->data['test_activity']->name],
                        ['Total Athletes', $this->data['total_athletes']],
                        ['Total Sessions', $this->data['total_sessions']],
                        ['Average Score', $this->data['average_score'] !== null ? number_format($this->data['average_score'], 1) . '%' : 'N/A'],
                        ['Conclusion', $this->data['conclusion'] ?? ''],
                    ];
                }
            },
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Athletes'; }
                public function headings(): array { return ['No', 'Athlete Number', 'Name', 'Gender', 'Date of Birth', 'Sport Branch']; }
                public function array(): array {
                    $rows = [];
                    foreach ($this->data['test_activity']->athletes as $index => $athlete) {
                        $rows[] = [
                            $index + 1,
                            $athlete->athlete_number,
                            $athlete->name,
                            $athlete->gender === 'M' ? 'Male' : 'Female',
                            $athlete->date_of_birth ? $athlete->date_of_birth->format('Y-m-d') : '-',
                            $athlete->sportBranch->name ?? '-',
                        ];
                    }
                    return $rows;
                }
            },
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Sessions'; }
                public function headings(): array { return ['No', 'Session Name', 'Location', 'Date Time']; }
                public function array(): array {
                    $rows = [];
                    foreach ($this->data['test_activity']->sessions as $index => $session) {
                        $rows[] = [
                            $index + 1,
                            $session->name,
                            $session->location,
                            $session->date_time,
                        ];
                    }
                    return $rows;
                }
            },
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Anthropometry'; }
                public function headings(): array { return ['No', 'Athlete Name', 'Height (cm)', 'Weight (kg)', 'BMI']; }
                public function array(): array {
                    $rows = [];
                    foreach ($this->data['test_activity']->athletes as $index => $athlete) {
                        $rows[] = [
                            $index + 1,
                            $athlete->name,
                            '-',
                            '-',
                            '-',
                        ];
                    }
                    return $rows;
                }
            },
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Raw Results'; }
                public function headings(): array { return ['No', 'Athlete Name', 'Session', 'Indicator', 'Trial No', 'Value', 'Unit']; }
                public function array(): array {
                    $rows = [];
                    $i = 1;
                    foreach ($this->data['test_activity']->sessions as $session) {
                        $session->load('trials.athlete', 'trials.indicator');
                        foreach ($session->trials as $trial) {
                            $rows[] = [
                                $i++,
                                $trial->athlete->name ?? 'N/A',
                                $session->name,
                                $trial->indicator->name ?? 'N/A',
                                $trial->trial_number,
                                $trial->value,
                                $trial->indicator->unit ?? '',
                            ];
                        }
                    }
                    return $rows;
                }
            },
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Scores'; }
                public function headings(): array { return ['No', 'Athlete Name', 'Overall Score']; }
                public function array(): array {
                    $rows = [];
                    foreach ($this->data['athlete_scores'] as $index => $item) {
                        $rows[] = [
                            $index + 1,
                            $item['athlete']->name,
                            $item['overall_score'] !== null ? number_format($item['overall_score'], 1) . '%' : 'N/A',
                        ];
                    }
                    return $rows;
                }
            },
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Benchmarks'; }
                public function headings(): array { return ['Sport Branch', 'Gender', 'Age Range', 'Indicator', 'Benchmark Value']; }
                public function array(): array {
                    $rows = [];
                    $athletes = $this->data['test_activity']->athletes;
                    $sportBranchIds = $athletes->pluck('sport_branch_id')->unique();
                    $benchmarks = Benchmark::whereIn('sport_branch_id', $sportBranchIds)->get();
                    foreach ($benchmarks as $b) {
                        $b->load('sportBranch');
                        foreach ($b->values as $indicatorId => $val) {
                            $ind = Indicator::find($indicatorId);
                            $rows[] = [
                                $b->sportBranch->name ?? 'N/A',
                                $b->gender === 'M' ? 'Male' : 'Female',
                                $b->age_min . ' - ' . $b->age_max . ' years',
                                $ind->name ?? 'N/A',
                                $val,
                            ];
                        }
                    }
                    return $rows;
                }
            },
            new class($this->data) implements FromArray, WithTitle, WithHeadings {
                private array $data;
                public function __construct($data) { $this->data = $data; }
                public function title(): string { return 'Metadata'; }
                public function headings(): array { return ['Key', 'Value']; }
                public function array(): array {
                    return [
                        ['Export Date', now()->format('Y-m-d H:i:s')],
                        ['Generated By', auth()->user()->name ?? 'System'],
                        ['App Version', '1.0'],
                    ];
                }
            },
        ];
    }
}
