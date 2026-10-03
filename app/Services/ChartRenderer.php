<?php

namespace App\Services;

interface ChartRenderer
{
    public function renderRadar(array $labels, array $data): ?string;
}
