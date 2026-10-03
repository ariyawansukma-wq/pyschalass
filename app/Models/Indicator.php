<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class Indicator extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
        });
    }

    public const CALC_BEST = 'BEST';
    public const CALC_AVERAGE = 'AVERAGE';
    public const CALC_LAST = 'LAST';

    protected $fillable = [
        'sport_branch_id',
        'name',
        'unit',
        'category',
        'scoring_direction',
        'calculation_method',
        'evaluation',
        'evaluation_threshold',
        'sort_order',
    ];

    public function sportBranch(): BelongsTo
    {
        return $this->belongsTo(SportBranch::class);
    }

    public function canBeDeleted(): bool
    {
        return true;
    }
}
