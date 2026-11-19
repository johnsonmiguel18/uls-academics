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
            ->merge($this->news()->published()->pluck('sdgs')->flatten());

        return $tagged->filter()->map(fn ($n) => (int) $n)->unique()->sort()->values()->all();
    }

    /** Stories per SDG (published news and extension projects), for the SDG grid. */
    public function sdgStoryCounts(): array
    {
        return collect($this->extension_projects ?? [])->pluck('sdgs')
            ->merge($this->news()->published()->pluck('sdgs'))
            ->flatten()->filter()->map(fn ($n) => (int) $n)
            ->countBy()->all();
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
