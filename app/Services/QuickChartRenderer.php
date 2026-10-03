<?php

namespace App\Services;

class QuickChartRenderer implements ChartRenderer
{
    public function renderRadar(array $labels, array $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        $chartConfig = [
            'type' => 'radar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Skor',
                        'data' => $data,
                        'backgroundColor' => 'rgba(5, 150, 105, 0.2)',
                        'borderColor' => 'rgb(5, 150, 105)',
                        'pointBackgroundColor' => 'rgb(5, 150, 105)',
                    ],
                ],
            ],
            'options' => [
                'scales' => [
                    'r' => [
                        'beginAtZero' => true,
                        'max' => 100,
                    ],
                ],
            ],
        ];

        $encoded = urlencode(json_encode($chartConfig));
        return "https://quickchart.io/chart?c={$encoded}&width=400&height=400";
    }
}
