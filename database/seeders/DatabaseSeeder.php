<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([SiteContentSeeder::class, AcademicsSeeder::class, EteeapUnitSeeder::class, CollegeColorsSeeder::class, GlobalPartnersSeeder::class]);
    }
}
