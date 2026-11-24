<?php

namespace App\Models;

use App\Support\NewsSeo;
use App\Support\Sdg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'college_id',
        'sdgs',
        'excerpt',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'keywords',
        'tags',
        'content',
        'featured_image',
        'status',
        'is_featured',
        'published_at',
        'unpublish_at',
    ];

    /** Stored statuses, in workflow order. "Scheduled" goes live by itself at published_at. */
    public const STATUSES = [
        'draft' => 'Draft',
        'review' => 'In review',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    /** What visitors see right now: [label, color] for badges and the publishing timeline. */
    public const STATES = [
        'draft' => ['Draft', '#6B7280'],
        'review' => ['In review', '#B45309'],
        'scheduled' => ['Scheduled', '#1D4ED8'],
        'live' => ['Live', '#15803D'],
        'expired' => ['Taken down', '#7C3AED'],
        'archived' => ['Archived', '#374151'],
    ];

    protected static function booted(): void
    {
        // Keep status and dates consistent however a story is saved (form, timeline drag, import):
        // "publish now" gets today's date, a future date means scheduled, a past one means published.
        static::saving(function (News $news): void {
            // The admin picker may send goal numbers as text; store numbers so SDG filters match.
            $news->sdgs = $news->sdgs ? array_values(array_unique(array_map('intval', $news->sdgs))) : null;

            // Search and sharing fields left empty (or still generated) are written from the story.
            NewsSeo::fill($news);

            if (! in_array($news->status, ['published', 'scheduled'])) {
                return;
            }

            $news->published_at ??= now();
            $news->status = $news->published_at->isFuture() ? 'scheduled' : 'published';
        });
    }

    /** Stories tagged with one SDG (also matches tags saved as text before they were normalized). */
    public function scopeForSdg(Builder $query, int $goal): void
    {
        $query->where(fn ($q) => $q->whereJsonContains('sdgs', $goal)->orWhereJsonContains('sdgs', (string) $goal));
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'sdgs' => 'array',
            'tags' => 'array',
            'published_at' => 'datetime',
            'unpublish_at' => 'datetime',
        ];
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * Live articles: published or scheduled, publication date reached, take-down date not reached.
     * Correct even if the scheduler (news:publish-scheduled) has not run yet.
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereIn('status', ['published', 'scheduled'])
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('unpublish_at')->orWhere('unpublish_at', '>', now()));
    }

    /**
     * Published stories on one goal: the number /news?sdg=N lists and /sdgs/N shows. Same scopes as the lists.
     */
    public static function sdgStoryCount(int $goal): int
    {
        return self::query()->published()->forSdg($goal)->count();
    }

    /**
     * Published stories per goal, [goal => stories], for the SDG report and the college SDG grids
     * (optionally one college's stories). Each story counts once per goal; agrees with sdgStoryCount().
     */
    public static function sdgStoryCounts(?int $collegeId = null): array
    {
        return self::query()->published()->whereNotNull('sdgs')
            ->when($collegeId, fn (Builder $q) => $q->where('college_id', $collegeId))
            ->pluck('sdgs')
            ->flatMap(fn ($tags) => Sdg::numbers($tags))
            ->countBy()->sortKeys()->all();
    }

    /** One of the STATES keys. */
    public function state(): string
    {
        return match (true) {
            in_array($this->status, ['draft', 'review', 'archived']) => $this->status,
            $this->unpublish_at?->isPast() => 'expired',
            $this->published_at?->isFuture() => 'scheduled',
            default => 'live',
        };
    }

    /**
     * Article body as one line of plain text, for previews.
     */
    public function plainText(): string
    {
        $html = preg_replace('/<br\s*\/?>|<\/(p|div|li|h[1-6])>/i', ' ', (string) $this->content);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html))));
    }

    /**
     * Short description: the editor's summary, or the start of the article.
     */
    public function summary(int $limit): string
    {
        return Str::limit($this->excerpt ?: $this->plainText(), $limit);
    }

    public function displayDate(): ?string
    {
        return ($this->published_at ?? $this->created_at)?->format('M d, Y');
    }
}
