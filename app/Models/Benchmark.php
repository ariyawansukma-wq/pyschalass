<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Benchmark extends Model
{
    use HasFactory;
    protected $fillable = [
        'sport_branch_id',
        'gender',
        'label',
        'age_min',
        'age_max',
        'values',
    ];

    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }

    public function sportBranch(): BelongsTo
    {
        return $this->belongsTo(SportBranch::class);
    }
}
