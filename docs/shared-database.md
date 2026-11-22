# One database for news and SDG pages

Decision (client, November 2026): "shared database" means **one database for this app**. The news pages and
the SDG pages read the same articles from it; there is no second copy of any article. Nothing here is shared
with other teams' apps, and nothing in this document changes any setting.

## What reads the articles

Every public page reads the `news` table through the `News` model and its `published()` scope (published or
scheduled, past its publish date, not taken down):

| Page | How it reads stories |
|---|---|
| `/news`, `/news?sdg=N`, `/news/{slug}`, lead story, More headlines | `NewsController`, `published()` (`forSdg()` for a goal) |
| `/sdgs`, `/sdgs/{goal}` | `SdgController`, `News::sdgStoryCounts()` and `News::sdgStoryCount()` |
| College pages (stories, SDG grid) | `AcademicsController`, `College::sdgStoryCounts()` (built on `News::sdgStoryCounts()`) |
| Home page news, header search suggestions, Global engagement stories | `published()` |
| Sample stories for the deans (`NewsDesignPreviewSeeder`) | Saved as ordinary `News` records, so they follow the same path |

The number of stories on a goal is counted in one place (`News::sdgStoryCount` / `sdgStoryCounts`), so
`/news?sdg=N`, `/sdgs/N`, the SDG report and the college grids cannot disagree. The SDG totals also count
college extension projects (`colleges.extension_projects`); each goal shows them separately as
"N stories · M projects". Tags are read the same way everywhere (`App\Support\Sdg::numbers()`: goals 1-17,
each once). `tests/Feature/SdgNewsSingleSourceTest.php` checks all of this.

Saving or deleting a story changes the content version, so every cached page is rebuilt on its next visit;
a cached page also never outlives the next scheduled publish or take-down (`CachePublicPages`).

## Database settings

The connection is set only in `.env` (see `.env.example`); the code has no other database settings:

| Variable | Production value |
|---|---|
| `DB_CONNECTION` | `mysql` (as in `.env.example`; Laravel falls back to `sqlite` only when this is missing) |
| `DB_HOST`, `DB_PORT` | The MySQL server, e.g. `127.0.0.1`, `3306` |
| `DB_DATABASE` | This app's own database, e.g. `uls_academics` |
| `DB_USERNAME`, `DB_PASSWORD` | An account for this database only |

Tests always use an in-memory SQLite database (`phpunit.xml`), never the real one.

This app owns its tables and migrations: run `php artisan migrate --force` from this app only.

## If other apps ever need the same data

Not planned. If it comes up, the safer route is for the other app to read this site's pages or a small read-only
API, not its tables. Sharing tables directly would mean: only one app may run migrations on them; the other
app must use a read-only account; renaming a column here could break the other app without warning; and the
other app would skip this app's publishing rules unless it copies the `published()` scope exactly.
