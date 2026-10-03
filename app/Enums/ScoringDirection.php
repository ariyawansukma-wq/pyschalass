<?php

namespace App\Enums;

enum ScoringDirection: string
{
    case HIGHER_IS_BETTER = 'HIGHER_IS_BETTER';
    case LOWER_IS_BETTER = 'LOWER_IS_BETTER';
}
