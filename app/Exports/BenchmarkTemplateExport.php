<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BenchmarkTemplateExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private ?int $sportBranchId;

    public function __construct(?int $sportBranchId = null)
    {
        $this->sportBranchId = $sportBranchId;
    }

    public function title(): string
    {
        return 'Template Benchmark';
    }

    public function array(): array
    {
        return [
            // Row 1: Top Merged Header
            ['KOMPONEN', 'METODE PENGUKURAN', 'SATUAN', '≤15 Tahun (70%)', '', '≥16 Tahun (80%)', '', 'HASIL EVALUASI'],
            // Row 2: Sub Header (Gender)
            ['', '', '', 'L', 'P', 'L', 'P', ''],
            // Row 3+: Data Sample Rows
            ['Flexibility', 'Sit & Reach', 'Meter', '22.4', '26.6', '25.6', '30.4', 'Lakukan latihan kelentukan rutin'],
            ['Speed', 'Sprint 30 Meter', 'Detik', '3.90', '4.16', '3.60', '3.84', 'Latihan acceleration & sprint drill'],
            ['Muscle Endurance', 'Sit Up 2 Menit', 'Kali', '77', '63', '88', '72', 'Tingkatkan kekuatan otot perut'],
            ['Muscle Endurance', 'Push Up 1 Menit', 'Kali', '60', '53', '60', '40', 'Tingkatkan kekuatan otot dada & lengan'],
            ['Muscle Endurance', 'Pull Up 1 Menit', 'Kali', '23', '15', '26', '17', 'Latihan otot punggung & bicep'],
            ['Muscle Endurance', 'Squat Jump 1 Menit', 'Kali', '62', '39', '70', '45', 'Tingkatkan daya tahan otot tungkai'],
            ['Muscle Endurance', 'Wall Sit', 'Detik', '126', '105', '144', '120', 'Latihan isometrik tungkai'],
            ['Agility', 'T-Test', 'Detik', '11.70', '13.00', '10.80', '12.80', 'Latihan kelincahan & koordinasi kaki'],
            ['Agility', 'Shuttle Run 8x5', 'Detik', '14.04', '16.08', '12.87', '14.74', 'Latihan kelincahan ubah arah'],
            ['Core Stability', 'Elbow Plank 6 Menit', 'Menit', '4.2', '4.2', '4.8', '4.8', 'Tingkatkan stabilitas inti tubuh (core)'],
            ['Core Stability', 'Core 12 Level', 'Level', '12', '12', '12', '12', 'Latihan penguatan core dasar'],
            ['Power', 'Medicine Ball 5 Kg (Chess)', 'Meter', '5.32', '4.97', '6.08', '5.68', 'Latihan eksplosif dada'],
            ['Power', 'Backward Throw MB', 'Meter', '6.86', '5.24', '8.16', '8.24', 'Latihan lempar bola beban'],
            ['Power', 'Standing MB Chest Throw', 'Meter', '8.4', '7', '9.6', '8', 'Latihan power dada'],
            ['Power', 'Seated MB Chest Throw', 'Meter', '5.32', '4.97', '6.08', '5.68', 'Latihan power posisi duduk'],
            ['Power', 'Shocken Test', 'Meter', '6.86', '5.24', '8.16', '8.24', 'Latihan eksplosif total body'],
            ['Power', 'Standing Broad Jump', 'Meter', '2.1', '1.91', '2.37', '2.15', 'Latihan lompat jauh tanpa awalan'],
            ['Power', 'Triple H.Jump (Kanan)', 'Meter', '7.40', '5.80', '8.50', '6.60', 'Latihan triple jump kaki kanan'],
            ['Power', 'Triple H.Jump (Kiri)', 'Meter', '7.10', '5.50', '8.20', '6.30', 'Latihan triple jump kaki kiri'],
            ['Power', 'Vertical Jump', 'Meter', '56', '46', '64', '52', 'Latihan daya ledak tungkai vertikal'],
            ['An Aerobic', 'Rast Test', 'Watt', '490', '350', '560', '400', 'Latihan an-aerobik pemulihan cepat'],
            ['Aerobic Capacity', 'Balke', 'ml/kg/min', '40.45', '34.42', '52.00', '44.25', 'Tingkatkan VO2Max lapangan'],
            ['Aerobic Capacity', 'Bleep Test', 'ml/kg/min', '40.45', '34.42', '52.00', '44.25', 'Tingkatkan daya tahan kardiovaskular VO2Max'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge Header Cells matching the reference image layout
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:E1');
                $sheet->mergeCells('F1:G1');
                $sheet->mergeCells('H1:H2');

                // Vertical merge for matching adjacent KOMPONEN (Column A)
                $dataRows = $this->array();
                $startIdx = null;
                $currentCat = null;
                $rowOffset = 3; // Data starts at Row 3

                for ($i = 0; $i < count($dataRows); $i++) {
                    $cat = $dataRows[$i][0] ?? '';
                    $rowNum = $rowOffset + $i;

                    if ($cat !== $currentCat) {
                        if ($startIdx !== null && ($rowNum - 1) > $startIdx) {
                            $sheet->mergeCells("A{$startIdx}:A" . ($rowNum - 1));
                        }
                        $currentCat = $cat;
                        $startIdx = $rowNum;
                    }
                }
                if ($startIdx !== null && ($rowOffset + count($dataRows) - 1) > $startIdx) {
                    $sheet->mergeCells("A{$startIdx}:A" . ($rowOffset + count($dataRows) - 1));
                }

                // Header styling (dark teal background & bold white text)
                $sheet->getStyle('A1:H2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F6E8C'],
                    ],
                ]);

                // Apply borders and vertical alignment across table
                $sheet->getStyle("A1:H{$totalRows}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Center align Satuan (Col C) and values (Cols D-G)
                $sheet->getStyle("C3:G{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
