<?php

namespace App\Support;

use App\Models\College;
use App\Models\News;
use App\Models\Term;
use Illuminate\Support\Str;

/**
 * Search and sharing fields of a news story: meta title and description, Open Graph title and description,
 * keywords and tags. Each is generated from the story (title, summary, article, category, college, SDGs)
 * unless an editor typed their own. A generated value follows later edits of the story; a typed one is kept.
 */
class NewsSeo
{
    public const FIELDS = ['meta_title', 'meta_description', 'og_title', 'og_description', 'keywords', 'tags'];

    /** Common words left out of keywords (English and Filipino). */
    private const STOP_WORDS = [
        'about', 'after', 'also', 'among', 'been', 'before', 'being', 'between', 'both', 'could', 'during', 'each', 'from',
        'have', 'into', 'just', 'like', 'many', 'more', 'most', 'much', 'other', 'over', 'said', 'same', 'some', 'such',
        'than', 'that', 'their', 'them', 'then', 'there', 'these', 'they', 'this', 'those', 'through', 'under', 'until',
        'very', 'were', 'what', 'when', 'where', 'which', 'while', 'will', 'with', 'within', 'without', 'would', 'your',
        'year', 'years', 'first', 'second', 'third', 'also', 'held', 'part', 'made', 'make', 'took', 'take', 'well',
        'ang', 'mga', 'para', 'nang', 'kanilang', 'ating', 'aming', 'siya', 'sila', 'kami', 'tayo', 'ito', 'iyon',
    ];

    /** The generated values for a story as it is now. */
    public static function auto(News $news): array
    {
        $text = $news->plainText();
        $summary = $news->excerpt ?: $text;
        $college = $news->college_id ? College::query()->find($news->college_id, ['id', 'name', 'short_name']) : null;

        $tags = array_values(array_unique(array_filter([
            $news->category ? Term::label('news_category', $news->category) : null,
            $college ? ($college->short_name ?: $college->name) : null,
            ...array_map(fn ($n) => 'SDG '.$n.(Sdg::name($n) ? ': '.Sdg::name($n) : ''), Sdg::numbers($news->sdgs ?? [])),
        ])));

        // Category and college, the story's own frequent words, then the goals; whole keywords only, up to 255 characters.
        $keywords = collect([
            ...array_slice(array_map(fn ($tag) => Str::before($tag, ':'), $tags), 0, 2),
            ...self::topWords(str_repeat($news->title.' ', 3).$text, 6),
            ...array_map(fn ($n) => Sdg::name($n), Sdg::numbers($news->sdgs ?? [])),
        ])->filter()->map(fn ($w) => trim(str_replace(',', '', $w)))->unique(fn ($w) => mb_strtolower($w))
            ->reduce(fn ($list, $w) => mb_strlen($list.', '.$w) > 255 ? $list : ($list === '' ? $w : "$list, $w"), '');

        return [
            'meta_title' => self::cut((string) $news->title, 70),
            'meta_description' => self::cut($summary, 160),
            'og_title' => self::cut((string) $news->title, 95),
            'og_description' => self::cut($summary, 200),
            'keywords' => $keywords,
            'tags' => array_slice($tags, 0, 8),
        ];
    }

    /** Called when a story is saved: empty fields, and fields still holding the old generated value, get new ones. */
    public static function fill(News $news): void
    {
        $now = self::auto($news);
        $before = $news->exists ? self::auto((new News)->setRawAttributes($news->getRawOriginal(), true)) : [];

        foreach (self::FIELDS as $field) {
            $value = $news->{$field};
            if (blank($value) || (array_key_exists($field, $before) && $value == $before[$field])) {
                $news->{$field} = $now[$field];
            }
        }
    }

    /** The values to print in the page head: stored ones, or generated ones for stories saved before these fields existed. */
    public static function for(News $news): array
    {
        $auto = null;
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = filled($news->{$field}) ? $news->{$field} : ($auto ??= self::auto($news))[$field];
        }

        return $values;
    }

    /** $text shortened at a word boundary to at most $max characters, with an ellipsis when cut. */
    private static function cut(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space > $max / 2 ? mb_substr($cut, 0, $space) : $cut, ' ,.;:-').'…';
    }

    /** The most frequent meaningful words, as written in the text. */
    private static function topWords(string $text, int $count): array
    {
        preg_match_all('/\p{L}[\p{L}\'-]{3,}/u', $text, $matches);
        $counts = [];
        $spelling = [];
        foreach ($matches[0] as $word) {
            $key = mb_strtolower($word);
            if (in_array($key, self::STOP_WORDS, true)) {
                continue;
            }
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            $spelling[$key] ??= $word;
        }
        arsort($counts);

        return array_map(fn ($key) => $spelling[$key], array_slice(array_keys(array_filter($counts, fn ($c) => $c > 1)), 0, $count));
    }
}
