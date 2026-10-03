<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Signatory;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $inst = Institution::create([
            'name' => 'West Java Province Youth and Sports Agency',
            'address' => 'Jl. Lembong No. 18, Bandung, West Java 40111',
            'phone' => '(022) 4231456',
            'email' => 'dispora@jabarprov.go.id',
            'header_lines' => "DISPORA PROVINSI JAWA BARAT\nDinas Pemuda dan Olahraga",
        ]);

        Signatory::create([
            'institution_id' => $inst->id,
            'name' => 'Dr. H. Asep Saepudin, M.Pd.',
            'position' => 'Head of Agency',
            'nip' => '196805151993031002',
        ]);

        Signatory::create([
            'institution_id' => $inst->id,
            'name' => 'Drs. H. Encep Nurdin, M.Si.',
            'position' => 'Secretary',
            'nip' => '197108121998031003',
        ]);

        Signatory::create([
            'institution_id' => $inst->id,
            'name' => 'Dr. Ir. Rahmat Hidayat, M.T.',
            'position' => 'Head of Sports Development Division',
            'nip' => '197505202003121004',
        ]);
    }
}
