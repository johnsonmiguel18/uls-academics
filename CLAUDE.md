# ULS Academics — project conventions

Laravel 12 + Filament 5 app (see README.md). Public styles are plain CSS in `public/css/`; there is no npm build.

## Content: never hardcode labels

- Every visitor-facing label, heading and button text is CMS-editable and comes from `App\Support\Site::text('key', [...placeholders])`
  (Site settings in the admin). Never write those strings into Blade templates.
- New labels need a key in Site settings, with a default seeded by `SiteContentSeeder`. That seeder fills missing keys only and never overwrites edits.
- Placeholders such as `:college` and `:short` are replaced by `Site::fill()`, longest names first.
- Lists (levels, degree types, categories, SDGs and their colors) come from Lists and categories. Don't hardcode them either.

## Assets

- Load every CSS/JS/image from `public/` through `App\Support\Asset::url('css/…')`, which adds a `?v=<mtime>` cache-buster.
  Don't use bare `asset()` or literal `/css/...` paths for these.

## Stylesheets per page type

`layouts/app.blade.php` loads `academics.css` on every page. Pages push their own sheets on top of it:

| Pages | Stylesheets | Class prefix |
|---|---|---|
| News (`news/index`, `news/show`) and home | `university.css` + `university-v3.css` + `news-card.css` | `uls-`, `news-` |
| SDG pages (`sdgs/*`) and college pages (`academics/college`) | `academics-*.css` (`academics-college.css`, `academics-sdgs.css`, …) | `ac-` |
| Program / finder | `academics-program.css` / `academics-finder.css` | `ac-` |

Don't mix the two families on one page, and don't add `ac-` classes to news/home markup (or `uls-` classes to SDG/college markup).

## Flag cross-file inconsistencies

When you see two sources that disagree (for example a version badge or a PHP/Laravel version in README vs the wiki,
composer.json vs composer.lock, docs vs code), report it to the user. Don't silently pick one side.

Known as of this file's creation:
- **PHP version**: the README says PHP 8.4. `composer.json` allows `^8.2`, but the locked packages need PHP ≥ 8.4.1
  (`symfony/html-sanitizer` v8.1.8 via Filament), so the code won't install on 8.2/8.3.
- **Home page CSS**: `academics/index.blade.php` loads only `academics.css` (layout) + `news-card.css`, not `university.css` /
  `university-v3.css` as the table above says.
- **News page CSS**: `news/index` and `news/show` load `university.css` + `university-v3.css` but not `news-card.css`.
- **Global page**: `global/index.blade.php` mixes `academics-college.css`, `global.css` and `news-card.css` (prefix `gl-`).
  This page type isn't in the table above.
- **Wiki**: there is no wiki in this repository, so README-vs-wiki badges can't be checked from here.
