<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * ETEEAP (Expanded Tertiary Education Equivalency and Accreditation Program) as its own unit, listed with the
 * colleges like Basic Education (deans' recommendation, November 2026), holding the ETEEAP degree tracks.
 * Safe to run again: the unit is created once and never overwritten, and only ETEEAP programs still waiting
 * as drafts under another college are moved and published.
 *
 *   php artisan db:seed --class=EteeapUnitSeeder
 */
class EteeapUnitSeeder extends Seeder
{
    public const SLUG = 'eteeap';

    public function run(): void
    {
        $unit = self::unit();

        $moved = Program::query()->where('degree_type', 'eteeap')->where('status', 'draft')
            ->where(fn ($q) => $q->whereNull('college_id')->orWhere('college_id', '!=', $unit->id))
            ->get();
        $moved->each->update(['college_id' => $unit->id, 'status' => 'published']);

        $this->command?->info("ETEEAP unit ready; {$moved->count()} ETEEAP program(s) moved into it and published.");
    }

    /** The ETEEAP unit, created when missing. Details from the university's current website (University officials). */
    public static function unit(): College
    {
        return College::query()->firstOrCreate(['slug' => self::SLUG], [
            'name' => 'Expanded Tertiary Education Equivalency and Accreditation Program',
            'short_name' => 'ETEEAP',
            'level' => 'undergraduate',
            'status' => 'published',
            'headline' => 'A degree for working adults',
            'overview' => 'ETEEAP grants a college degree to working professionals by crediting the knowledge and skills they gained at work, through an assessment of their learning and experience.',
            'dean_name' => 'Jaypee Fernan M. Ramirez, LPT, MBA',
            'dean_title' => 'ETEEAP Coordinator',
            'sort_order' => (int) College::query()->max('sort_order') + 1,
        ]);
    }
}
