<?php

namespace Database\Seeders;

use App\Models\Athlete;
use App\Models\SportBranch;
use Illuminate\Database\Seeder;

class AthleteSeeder extends Seeder
{
    public function run(): void
    {
        $branches = SportBranch::all()->keyBy('name');

        $athletes = [
            // === Athletics (10 athletes) ===
            ['name' => 'Budi Santoso',       'gender' => 'M', 'dob' => '2005-03-15', 'branch' => 'Athletics', 'h' => 172.5, 'w' => 62.0],
            ['name' => 'Andi Pratama',       'gender' => 'M', 'dob' => '2004-08-22', 'branch' => 'Athletics', 'h' => 175.0, 'w' => 68.5],
            ['name' => 'Rizky Ramadhan',     'gender' => 'M', 'dob' => '2006-01-10', 'branch' => 'Athletics', 'h' => 168.0, 'w' => 58.0],
            ['name' => 'Gilang Ramadan',     'gender' => 'M', 'dob' => '2005-05-20', 'branch' => 'Athletics', 'h' => 174.0, 'w' => 65.0],
            ['name' => 'Yusuf Maulana',      'gender' => 'M', 'dob' => '2004-10-11', 'branch' => 'Athletics', 'h' => 169.5, 'w' => 60.5],
            ['name' => 'Dewi Sartika',       'gender' => 'F', 'dob' => '2005-06-18', 'branch' => 'Athletics', 'h' => 160.5, 'w' => 52.0],
            ['name' => 'Siti Nurhaliza',     'gender' => 'F', 'dob' => '2004-11-30', 'branch' => 'Athletics', 'h' => 163.0, 'w' => 55.5],
            ['name' => 'Rina Wati',          'gender' => 'F', 'dob' => '2005-09-05', 'branch' => 'Athletics', 'h' => 158.0, 'w' => 50.0],
            ['name' => 'Lestari Wulan',      'gender' => 'F', 'dob' => '2006-01-25', 'branch' => 'Athletics', 'h' => 157.0, 'w' => 48.0],
            ['name' => 'Fitri Handayani',    'gender' => 'F', 'dob' => '2003-07-14', 'branch' => 'Athletics', 'h' => 165.0, 'w' => 57.0],

            // === Swimming (8 athletes) ===
            ['name' => 'Dimas Prayoga',      'gender' => 'M', 'dob' => '2004-04-12', 'branch' => 'Swimming', 'h' => 178.0, 'w' => 72.0],
            ['name' => 'Fajar Nugroho',      'gender' => 'M', 'dob' => '2005-07-25', 'branch' => 'Swimming', 'h' => 180.5, 'w' => 75.0],
            ['name' => 'Aditya Wijaya',      'gender' => 'M', 'dob' => '2006-02-14', 'branch' => 'Swimming', 'h' => 170.0, 'w' => 63.0],
            ['name' => 'Rangga Saputra',     'gender' => 'M', 'dob' => '2004-07-30', 'branch' => 'Swimming', 'h' => 176.0, 'w' => 70.0],
            ['name' => 'Maya Putri',         'gender' => 'F', 'dob' => '2005-10-08', 'branch' => 'Swimming', 'h' => 165.0, 'w' => 57.0],
            ['name' => 'Anisa Rahma',        'gender' => 'F', 'dob' => '2004-12-20', 'branch' => 'Swimming', 'h' => 167.5, 'w' => 59.5],
            ['name' => 'Putri Amelia',       'gender' => 'F', 'dob' => '2006-02-28', 'branch' => 'Swimming', 'h' => 162.0, 'w' => 54.0],
            ['name' => 'Kartika Sari',       'gender' => 'F', 'dob' => '2003-09-17', 'branch' => 'Swimming', 'h' => 170.0, 'w' => 62.0],

            // === Football (10 athletes) ===
            ['name' => 'Hendra Kurniawan',   'gender' => 'M', 'dob' => '2004-05-30', 'branch' => 'Football', 'h' => 174.0, 'w' => 66.0],
            ['name' => 'Rudi Hartono',       'gender' => 'M', 'dob' => '2005-09-14', 'branch' => 'Football', 'h' => 171.0, 'w' => 64.5],
            ['name' => 'Agung Setiawan',     'gender' => 'M', 'dob' => '2004-01-22', 'branch' => 'Football', 'h' => 176.5, 'w' => 70.0],
            ['name' => 'Tono Sugiarto',      'gender' => 'M', 'dob' => '2006-03-08', 'branch' => 'Football', 'h' => 169.0, 'w' => 60.0],
            ['name' => 'Bayu Firmansyah',    'gender' => 'M', 'dob' => '2005-11-17', 'branch' => 'Football', 'h' => 173.0, 'w' => 65.0],
            ['name' => 'Rahmat Hidayat',     'gender' => 'M', 'dob' => '2005-08-03', 'branch' => 'Football', 'h' => 175.5, 'w' => 67.0],
            ['name' => 'Irfan Bachdim',      'gender' => 'M', 'dob' => '2003-12-05', 'branch' => 'Football', 'h' => 178.0, 'w' => 73.0],
            ['name' => 'Citra Kirana',       'gender' => 'F', 'dob' => '2005-03-14', 'branch' => 'Football', 'h' => 159.0, 'w' => 52.0],
            ['name' => 'Novia Cahaya',       'gender' => 'F', 'dob' => '2004-06-21', 'branch' => 'Football', 'h' => 161.0, 'w' => 54.0],
            ['name' => 'Rina Anggraini',     'gender' => 'F', 'dob' => '2006-04-09', 'branch' => 'Football', 'h' => 157.5, 'w' => 50.5],

            // === Badminton (8 athletes) ===
            ['name' => 'Kevin Sanjaya',      'gender' => 'M', 'dob' => '2004-07-04', 'branch' => 'Badminton', 'h' => 170.0, 'w' => 62.0],
            ['name' => 'Anthony Ginting',    'gender' => 'M', 'dob' => '2005-02-28', 'branch' => 'Badminton', 'h' => 171.5, 'w' => 63.5],
            ['name' => 'Wahyu Pratama',      'gender' => 'M', 'dob' => '2004-11-06', 'branch' => 'Badminton', 'h' => 173.0, 'w' => 64.0],
            ['name' => 'Jonatan Christie',   'gender' => 'M', 'dob' => '2003-09-15', 'branch' => 'Badminton', 'h' => 175.0, 'w' => 68.0],
            ['name' => 'Gregoria Mariska',   'gender' => 'F', 'dob' => '2005-08-11', 'branch' => 'Badminton', 'h' => 164.0, 'w' => 54.0],
            ['name' => 'Greysia Polii',      'gender' => 'F', 'dob' => '2004-10-03', 'branch' => 'Badminton', 'h' => 162.0, 'w' => 53.0],
            ['name' => 'Dian Sastro',        'gender' => 'F', 'dob' => '2004-04-17', 'branch' => 'Badminton', 'h' => 161.0, 'w' => 51.0],
            ['name' => 'Susi Susanti',       'gender' => 'F', 'dob' => '2003-02-11', 'branch' => 'Badminton', 'h' => 163.5, 'w' => 55.0],

            // === Volleyball (9 athletes) ===
            ['name' => 'Rivan Nurmulki',     'gender' => 'M', 'dob' => '2004-06-16', 'branch' => 'Volleyball', 'h' => 185.0, 'w' => 78.0],
            ['name' => 'Sigit Ardian',       'gender' => 'M', 'dob' => '2005-04-09', 'branch' => 'Volleyball', 'h' => 182.0, 'w' => 74.0],
            ['name' => 'Fikri Akbar',        'gender' => 'M', 'dob' => '2005-12-09', 'branch' => 'Volleyball', 'h' => 188.0, 'w' => 80.0],
            ['name' => 'Hendri Kurniawan',   'gender' => 'M', 'dob' => '2003-08-22', 'branch' => 'Volleyball', 'h' => 190.0, 'w' => 82.0],
            ['name' => 'Doni Haryono',       'gender' => 'M', 'dob' => '2006-01-05', 'branch' => 'Volleyball', 'h' => 184.0, 'w' => 76.0],
            ['name' => 'Nandita Ayu',        'gender' => 'F', 'dob' => '2005-12-01', 'branch' => 'Volleyball', 'h' => 170.0, 'w' => 60.0],
            ['name' => 'Aprilio Manganang',  'gender' => 'F', 'dob' => '2004-09-27', 'branch' => 'Volleyball', 'h' => 172.5, 'w' => 63.0],
            ['name' => 'Mega Putri',         'gender' => 'F', 'dob' => '2005-06-22', 'branch' => 'Volleyball', 'h' => 168.0, 'w' => 58.0],
            ['name' => 'Wilda Nurfadhilah',  'gender' => 'F', 'dob' => '2003-11-18', 'branch' => 'Volleyball', 'h' => 175.0, 'w' => 65.0],
        ];

        foreach ($athletes as $a) {
            $h = $a['h'];
            $w = $a['w'];
            $bmi = round($w / (($h / 100) ** 2), 2);

            Athlete::create([
                'athlete_number' => 'ATL-' . str_pad(Athlete::count() + 1, 4, '0', STR_PAD_LEFT),
                'name' => $a['name'],
                'gender' => $a['gender'],
                'date_of_birth' => $a['dob'],
                'sport_branch_id' => $branches[$a['branch']]->id,
                'height' => $h,
                'weight' => $w,
                'bmi' => $bmi,
            ]);
        }
    }
}
