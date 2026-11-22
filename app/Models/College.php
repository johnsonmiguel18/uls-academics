<?php

namespace App\Models;

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

        return \App\Support\Sdg::numbers($tagged->all());
    }

    /**
     * Stories and extension projects per SDG, for the college SDG grid: the college's published stories
     * counted as on the news pages (News::sdgStoryCounts), plus its projects; each item once per goal.
     */
    public function sdgStoryCounts(): array
    {
        $counts = News::sdgStoryCounts($this->id);
        foreach ($this->extension_projects ?? [] as $project) {
            foreach (\App\Support\Sdg::numbers($project['sdgs'] ?? []) as $goal) {
                $counts[$goal] = ($counts[$goal] ?? 0) + 1;
            }
        }
        ksort($counts);

        return $counts;
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
