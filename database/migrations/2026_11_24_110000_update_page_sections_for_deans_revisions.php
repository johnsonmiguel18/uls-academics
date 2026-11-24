<?php

use App\Support\ContentVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deans' recommendations (November 2026), for sites whose settings already exist (SiteContentSeeder never merges
 * lists): the college "Extension & SDGs" section becomes "Extension" (no SDG grid), colleges get an Accreditation
 * section, the home page a Recognitions section, and the "SDGs supported" college number is switched off.
 * Wording edited in the admin is kept; only the old default wording is replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->change(function (array $data): array {
            if (is_array($data['college']['sections'] ?? null)) {
                $sections = [];
                foreach ($data['college']['sections'] as $section) {
                    if (($section['type'] ?? null) === 'sdgs') {
                        $section['type'] = 'extension';
                        $section = $this->replaceDefault($section, 'nav_label', 'Extension & SDGs', 'Extension');
                        $section = $this->replaceDefault($section, 'kicker', 'Extension and the SDGs', 'Community extension');
                    }
                    $sections[] = $section;
                    if (($section['type'] ?? null) === 'partners') {
                        $sections[] = ['type' => 'accreditation', 'enabled' => true, 'nav_label' => 'Accreditation', 'kicker' => 'Quality assurance', 'heading' => 'Accreditation and recognition'];
                    }
                }
                if (! in_array('accreditation', array_column($sections, 'type'), true)) { // no Partners section to follow
                    $sections[] = ['type' => 'accreditation', 'enabled' => true, 'nav_label' => 'Accreditation', 'kicker' => 'Quality assurance', 'heading' => 'Accreditation and recognition'];
                }
                $data['college']['sections'] = $this->unique($sections, 'accreditation');
            }

            if (is_array($data['college']['stats'] ?? null)) {
                $data['college']['stats'] = array_map(fn ($s) => ($s['metric'] ?? null) === 'sdgs' ? ['enabled' => false] + $s : $s, $data['college']['stats']);
            }

            if (is_array($data['home']['sections'] ?? null)) {
                $sections = [];
                foreach ($data['home']['sections'] as $section) {
                    $sections[] = $section;
                    if (($section['type'] ?? null) === 'featured') {
                        $sections[] = ['type' => 'recognitions', 'enabled' => true, 'kicker' => 'Quality and recognition', 'heading' => 'Accreditation, rankings and recognition', 'link_label' => '', 'limit' => 6];
                    }
                }
                if (! in_array('recognitions', array_column($sections, 'type'), true)) {
                    $sections[] = ['type' => 'recognitions', 'enabled' => true, 'kicker' => 'Quality and recognition', 'heading' => 'Accreditation, rankings and recognition', 'link_label' => '', 'limit' => 6];
                }
                $data['home']['sections'] = $this->unique($sections, 'recognitions');
            }

            return $data;
        });
    }

    public function down(): void
    {
        $this->change(function (array $data): array {
            if (is_array($data['college']['sections'] ?? null)) {
                $data['college']['sections'] = array_values(array_map(function ($section) {
                    if (($section['type'] ?? null) === 'extension') {
                        $section['type'] = 'sdgs';
                        $section = $this->replaceDefault($section, 'nav_label', 'Extension', 'Extension & SDGs');
                        $section = $this->replaceDefault($section, 'kicker', 'Community extension', 'Extension and the SDGs');
                    }

                    return $section;
                }, array_filter($data['college']['sections'], fn ($s) => ($s['type'] ?? null) !== 'accreditation')));
            }
            if (is_array($data['home']['sections'] ?? null)) {
                $data['home']['sections'] = array_values(array_filter($data['home']['sections'], fn ($s) => ($s['type'] ?? null) !== 'recognitions'));
            }

            return $data;
        });
    }

    private function change(callable $edit): void
    {
        $row = DB::table('site_settings')->first();
        if (! $row) {
            return; // a new site: SiteContentSeeder writes the new sections
        }
        $data = json_decode((string) $row->data, true) ?: [];
        DB::table('site_settings')->where('id', $row->id)->update(['data' => json_encode($edit($data)), 'updated_at' => now()]);
        ContentVersion::bump(); // cached pages are rebuilt with the new sections
    }

    private function replaceDefault(array $section, string $key, string $old, string $new): array
    {
        if (($section[$key] ?? null) === $old) {
            $section[$key] = $new;
        }

        return $section;
    }

    /** Keeps the first section of $type (an earlier run or a manual edit may have added one already). */
    private function unique(array $sections, string $type): array
    {
        $seen = false;

        return array_values(array_filter($sections, function ($s) use ($type, &$seen) {
            if (($s['type'] ?? null) !== $type) {
                return true;
            }

            return $seen ? false : $seen = true;
        }));
    }
};
