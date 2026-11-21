# ULS Academics — project conventions

Laravel 12 + Filament 5 app (see README.md). Public styles are plain CSS in `public/css/`. The only build is Vite for
`resources/js/site.js` (obfuscated on build, no source maps; output in `public/build`, which is not committed).

## Content: never hardcode labels

- Every visitor-facing label, heading and button text is CMS-editable and comes from `App\Support\Site::text('key', [...placeholders])`
  (Site settings in the admin). Never write those strings into Blade templates, not even as fallbacks.
- New labels need a key in Site settings, with a default seeded by `SiteContentSeeder`. That seeder fills missing keys only and never overwrites edits.
  Screen-reader-only labels live under `a11y.*`, news labels under `news.labels.*`.
- Placeholders such as `:college` and `:short` are replaced by `Site::fill()`, longest names first.
- Lists (levels, degree types, categories, SDGs and their colors) come from Lists and categories. Don't hardcode them either.

## Assets

- Load every CSS/JS/image from `public/` through `App\Support\Asset::url('css/…')`, which adds a `?v=<mtime>` cache-buster.
  Don't use bare `asset()` or literal `/css/...` paths for these. Vite assets go through the `Asset::viteReady()` guard.

## Stylesheets per page type

`layouts/app.blade.php` loads `academics.css` on every page. Pages push their own sheets on top of it:

| Pages | Stylesheets | Class prefix |
|---|---|---|
| News (`news/index`, `news/show`) | `university.css` + `university-v3.css` + `news-card.css` | `uls-`, `news-` |
| Home (`academics/index`) | `news-card.css` | `ac-`, `news-` |
| College (`academics/college`) | `academics-college.css` + `news-card.css` | `ac-`, `news-` |
| SDG pages (`sdgs/*`) | `academics-college.css` + `academics-sdgs.css` | `ac-` |
| Program / finder | `academics-program.css` / `academics-finder.css` | `ac-` |
| Global (`global/index`) | `academics-college.css` + `global.css` + `news-card.css` | `gl-`, `ac-`, `news-` |

`news-card.css` is the shared card component (news cards and SDG labels); it is the one sheet used across both families.

## Security and accessibility

- `SecurityHeaders` middleware sets the CSP (nonce on public pages). Inline scripts need the nonce; no new external hosts without asking.
- Public pages pass axe (WCAG 2.2 AA) with 0 violations and work by keyboard. Keep it that way: name every landmark and
  form, don't add `title` tooltips that repeat visible or hidden text, and don't change colors for accessibility without asking.

## Git workflow

- Typed branches cut from an up-to-date main (`git fetch` first): `feature/`, `bugfix/`, `hotfix/`, `ui/`, `chore/`, `docs/`.
- One branch per change: commit, push the branch, `git merge --no-ff` into main, push main. No pull requests unless asked.
- Commit messages: imperative plain-English subject, `-` bullet body, co-author trailers in one block at the end.
- SemVer. Tag and release only when the owner says so; every tag gets a GitHub Release with notes written from the commits,
  including what wasn't tested. Never move or delete a pushed tag, force-push, or rewrite history without asking.
- Never commit secrets (`.env`, credentials, local settings files). README badges use brand colors; no emojis.

## Flag cross-file inconsistencies

When you see two sources that disagree (for example a version badge or a PHP/Laravel version in README vs the wiki,
composer.json vs composer.lock, docs vs code), report it to the user. Don't silently pick one side.

Known:
- **PHP version**: the README says PHP 8.4. `composer.json` allows `^8.2`, but the locked packages need PHP ≥ 8.4.1
  (`symfony/html-sanitizer` via Filament), so the code won't install on 8.2/8.3. On PHP 8.3, two tests fail and the
  college and program pages return errors.
- **Wiki**: there is no wiki in this repository, so README-vs-wiki badges can't be checked from here.
