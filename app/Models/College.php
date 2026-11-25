<?php

namespace App\Models;

use App\Support\Sdg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class College extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'research_highlights' => 'array',
            'focus_areas' => 'array',
            'partners' => 'array',
            'linkages' => 'array',
            'student_benefits' => 'array',
            'extension_projects' => 'array',
        ];
    }

    /** Every SDG this college works on: focus areas, partners, extension projects and tagged news. */
    public function sdgsSupported(): array
    {
        $tagged = collect([$this->focus_areas, $this->partners, $this->extension_projects])
            ->flatMap(fn ($items) => collect($items ?? [])->pluck('sdgs')->flatten())
            ->merge(array_keys(News::sdgStoryCounts($this->id)));

        return Sdg::numbers($tagged->all());
    }

    /**
     * Stories and extension projects per SDG, for the college SDG grid: the college's published stories
     * counted as on the news pages (News::sdgStoryCounts), plus its projects; each item once per goal.
     */
    public function sdgStoryCounts(): array
    {
        $counts = News::sdgStoryCounts($this->id);
        foreach ($this->extension_projects ?? [] as $project) {
            foreach (Sdg::numbers($project['sdgs'] ?? []) as $goal) {
                $counts[$goal] = ($counts[$goal] ?? 0) + 1;
            }
        }
        ksort($counts);

        return $counts;
    }

    /** The college's own color (Colleges → edit), or the site navy when none is set. */
    public function accent(): string
    {
        return preg_match('/^#[0-9a-f]{6}$/i', (string) $this->color) ? strtoupper($this->color) : '#192A8A';
    }

    /** The college color, darkened until text in it reaches 4.5:1 on white (WCAG AA), for the college name. */
    public function accentInk(): string
    {
        [$r, $g, $b] = sscanf($this->accent(), '#%02x%02x%02x');
        for ($i = 0; $i < 20 && Sdg::contrast(sprintf('#%02X%02X%02X', $r, $g, $b), '#FFFFFF') < 4.5; $i++) {
            [$r, $g, $b] = [(int) ($r * 0.88), (int) ($g * 0.88), (int) ($b * 0.88)];
        }

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /** Text color on the college color (white or black, whichever reads better). */
    public function accentOn(): string
    {
        return Sdg::ink($this->accent());
    }

    /** Up to three initials for colleges without a seal, e.g. "CL" for College of Law. */
    public function monogram(): string
    {
        $words = collect(preg_split('/\s+/', $this->short_name ?: $this->name))
            ->reject(fn ($w) => in_array(mb_strtolower($w), ['of', 'and', 'the', '&', '-'], true));

        return $this->short_name ?: mb_strtoupper($words->map(fn ($w) => mb_substr($w, 0, 1))->take(3)->implode(''));
    }

    /** YouTube id of the college video, for its thumbnail; null for other links. */
    public function youtubeId(): ?string
    {
        preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', (string) $this->video_url, $m);

        return $m[1] ?? null;
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')->orderBy('sort_order')->orderBy('name');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class)->orderBy('sort_order')->orderBy('name');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class)->orderBy('sort_order')->orderBy('name');
    }

    public function faculty(): HasMany
    {
        return $this->hasMany(FacultyMember::class)->where('is_published', true)->orderBy('sort_order')->orderBy('name');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AcademicEvent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->where('is_public', true)->latest();
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }
}
