<?php

namespace Tests\Unit;

use App\Services\AssessmentReport;
use Tests\TestCase;

class AssessmentReportTest extends TestCase
{
    public function test_assessment_report_instantiation_and_array_conversion(): void
    {
        $payload = [
            'name' => 'Report 1',
            'overall' => 85.5,
        ];

        $report = new AssessmentReport($payload);

        $this->assertEquals($payload, $report->toArray());
    }

    public function test_assessment_report_get_and_set(): void
    {
        $report = new AssessmentReport(['initial' => 'value']);

        $this->assertEquals('value', $report->get('initial'));
        $this->assertNull($report->get('non_existent'));
        $this->assertEquals('default', $report->get('non_existent', 'default'));

        $report->set('new_key', 'new_value');
        $this->assertEquals('new_value', $report->get('new_key'));
    }

    public function test_assessment_report_to_array_resolves_nested_reports(): void
    {
        $subReport = new AssessmentReport(['child_name' => 'Child Report']);
        
        $parentReport = new AssessmentReport([
            'parent_name' => 'Parent Report',
            'individual_reports' => [
                'athlete_1' => $subReport,
            ]
        ]);

        $resolved = $parentReport->toArray();

        $this->assertIsArray($resolved['individual_reports']['athlete_1']);
        $this->assertEquals('Child Report', $resolved['individual_reports']['athlete_1']['child_name']);
    }
}
