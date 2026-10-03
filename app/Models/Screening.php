<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Screening extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_anak',
        'umur_bulan',
        'skor_statis',
        'skor_tandem',
        'skor_lompat',
        'skor_sit_to_stand',
        'skor_vestibular',
        'total_skor',
        'kategori',
        'chart_data',
    ];

    protected function casts(): array
    {
        return [
            'chart_data'   => 'array',
            'umur_bulan'   => 'integer',
            'total_skor'   => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
