<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anthropometry extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'athlete_id',
        'height',
        'weight',
        'bmi',
    ];

    protected function casts(): array
    {
        return [
            'height' => 'decimal:1',
            'weight' => 'decimal:1',
            'bmi' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }
}
