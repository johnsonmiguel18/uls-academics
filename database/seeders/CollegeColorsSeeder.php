<?php

namespace Database\Seeders;

use App\Models\College;
use Illuminate\Database\Seeder;

/**
 * A starting color for each college (deans' recommendation: "each college must have its own designated color").
 * Only colleges without a color get one, so colors chosen in the admin (Colleges → edit → College color) stay.
 * These are distinct placeholders, to be replaced with each college's official color.
 *
 *   php artisan db:seed --class=CollegeColorsSeeder
 */
class CollegeColorsSeeder extends Seeder
{
    public const COLORS = [
        'college-of-nursing-public-health-and-midwifery' => '#BE185D',
        'college-of-information-technology' => '#0E7490',
        'college-of-medicine-and-allied-medical-programs' => '#15803D',
        'college-of-law' => '#6B21A8',
        'college-of-business-education' => '#0F766E',
        'college-of-accountancy' => '#854D0E',
        'college-of-engineering-and-architecture' => '#EA580C',
        'college-of-criminal-justice-education' => '#991B1B',
        'college-of-arts-and-sciences' => '#CA8A04',
        'college-of-teacher-education' => '#2563EB',
        'graduate-school' => '#1F2937',
        'basic-education' => '#65A30D',
        'eteeap' => '#C026D3',
    ];

    public function run(): void
    {
        $set = 0;
        foreach (self::COLORS as $slug => $color) {
            $set += College::query()->where('slug', $slug)->whereNull('color')->update(['color' => $color]);
        }
        $this->command?->info("College colors: $set set (colleges that already had one were left as they are).");
    }
}
