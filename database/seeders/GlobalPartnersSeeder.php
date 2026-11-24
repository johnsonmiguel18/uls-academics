<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Support\ContentVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * University-wide partners from the Office of Global Engagement's list (ULS GLOBAL, "Salette Internationalization
 * Framework", November 2026), with their logos. Adds only partners not listed yet (matched by name), so it is safe
 * to run again and never changes partners edited in the admin (Site settings → Global engagement → Partners).
 *
 *   php artisan db:seed --class=GlobalPartnersSeeder
 */
class GlobalPartnersSeeder extends Seeder
{
    /** [name, type, local|international, country, logo file in data/partners or null] */
    public const PARTNERS = [
        ['Universidade da Paz (UNPAZ)', 'Partner university', 'international', 'Dili, Timor-Leste', 'unpaz.jpg'],
        ['National Chi Nan University (NCNU)', 'Partner university', 'international', 'Taiwan', 'ncnu.png'],
        ['Institut Keguruan Dan Teknologi Larantuka (IKTL)', 'Partner institute', 'international', 'Indonesia', 'iktl.jpg'],
        ['HLU-WURI', 'Multilateral agreement', 'international', null, null],
        ['AUAP-WURI', 'Multilateral agreement', 'international', null, null],
        ['University of Cagayan Valley (UCV)', 'Partner university', 'local', 'Tuguegarao, Cagayan Valley', 'ucv.jpg'],
        ['University of Saint Louis Tuguegarao (USL)', 'Partner university', 'local', 'Tuguegarao, Cagayan Valley', 'usl-tuguegarao.jpg'],
        ['Panpacific University', 'Partner university', 'local', 'Urdaneta City, Pangasinan', 'panpacific.png'],
        ['Urdaneta City University (UCU)', 'Partner university', 'local', 'Urdaneta City, Pangasinan', 'ucu.png'],
        ['De La Salle University-Dasmariñas (DLSU-D)', 'Partner university', 'local', 'Dasmariñas, Cavite', 'dlsu-dasmarinas.png'],
        ['Philippine National Volunteer Service Coordinating Agency (PNVSCA)', 'Agency partner', 'local', 'Philippines', 'pnvsca.png'],
        ['Philippine National Red Cross, Santiago City Chapter', 'Agency partner', 'local', 'Santiago City, Isabela', 'red-cross-santiago.png'],
    ];

    public function run(): void
    {
        $setting = SiteSetting::query()->firstOrCreate([]);
        $data = $setting->data ?? [];
        $partners = $data['global']['partners'] ?? [];
        $known = collect($partners)->pluck('name')->map(fn ($n) => mb_strtolower(trim((string) $n)))->all();
        $added = 0;

        foreach (self::PARTNERS as [$name, $type, $scope, $country, $logo]) {
            if (in_array(mb_strtolower($name), $known, true)) {
                continue;
            }
            $partners[] = array_filter([
                'name' => $name,
                'type' => $type,
                'scope' => $scope,
                'country' => $country,
                'logo' => $logo ? $this->logo($logo) : null,
            ], fn ($v) => $v !== null);
            $added++;
        }

        if ($added) {
            $data['global']['partners'] = $partners;
            $setting->data = $data;
            $setting->save();
            ContentVersion::bump();
        }

        $this->command?->info("Global partners: $added added, ".(count(self::PARTNERS) - $added).' already listed.');
    }

    /** Copies a logo onto the public disk (partners/), keeping a file already there, and returns its path. */
    private function logo(string $file): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists("partners/$file")) {
            $disk->put("partners/$file", File::get(database_path("seeders/data/partners/$file")));
        }

        return "partners/$file";
    }
}
