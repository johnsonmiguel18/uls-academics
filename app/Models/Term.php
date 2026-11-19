<?php

namespace App\Models;

use App\Support\ContentVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-managed lists (Admin → Lists and categories). Records store the term "key",
 * so renaming a label never breaks existing content.
 */
class Term extends Model
{
    /** The kinds of list the site uses. The entries themselves live in the database. */
    public const TYPES = [
        'level' => 'Academic levels',
        'degree_type' => 'Degree types',
        'news_category' => 'News categories',
        'document_category' => 'Document categories',
        'sdg' => 'Sustainable Development Goals',
        'partner_scope' => 'Partner reach (local, international)',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Whether any record still stores this entry's key. */
    public function isInUse(): bool
    {
        return match ($this->type) {
            'level' => Program::where('level', $this->key)->exists() || College::where('level', $this->key)->exists(),
            'degree_type' => Program::where('degree_type', $this->key)->exists(),
            'news_category' => News::where('category', $this->key)->exists(),
            'document_category' => Document::where('category', $this->key)->exists(),
            'partner_scope' => College::where('partners', 'like', '%"scope":"' . $this->key . '"%')->exists()
                || str_contains(json_encode(SiteSetting::query()->value('data')), '"scope":"' . $this->key . '"'),
            default => true, // SDGs are referenced inside college sections and news tags: hide instead
        };
    }

    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /** [key => label] of the active entries of a list, in admin order. */
    public static function options(string $type): array
    {
        return array_map(fn ($term) => $term['label'], self::lists()[$type] ?? []);
    }

    /** Label for a stored key; the key itself if the entry was deleted. */
    public static function label(string $type, ?string $key): string
    {
        return self::lists()[$type][$key]['label'] ?? (string) $key;
    }

    /** [key => ['label' => ..., 'color' => ..., 'description' => ...]] of a list. */
    public static function entries(string $type): array
    {
        return self::lists()[$type] ?? [];
    }

    private static function lists(): array
    {
        return once(fn () => Cache::rememberForever('terms:' . ContentVersion::current(), fn () => self::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['type', 'key', 'label', 'color', 'description'])
            ->groupBy('type')
            ->map(fn ($terms) => $terms->mapWithKeys(fn ($t) => [$t->key => ['label' => $t->label, 'color' => $t->color, 'description' => $t->description]])->all())
            ->all()));
    }
}
