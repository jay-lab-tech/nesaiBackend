<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Data ASLI SMKN 1 Subang: school, majors, alumni, sarana, ekskul,
        // karya inovasi, statistik SPMB & tracking alumni.
        $this->call(SchoolPublicDataSeeder::class);

        // Data PLACEHOLDER untuk News/PPDB/FAQ — ganti begitu konten resmi tersedia.
        $this->call(DummySchoolDataSeeder::class);
    }
}