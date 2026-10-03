<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KaryaIlmiah extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'jenis',
        'tahun',
        'deskripsi',
        'file_path',
        'link_eksternal',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }
}
