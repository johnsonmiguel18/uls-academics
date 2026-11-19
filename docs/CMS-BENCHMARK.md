# ULS Academics CMS: benchmark against business content managers

How the publishing features of ULS Academics compare with widely used content
management systems (checked November 2026 from each vendor's documentation).

| Capability | WordPress | Contentful | Sanity | HubSpot | ULS Academics |
|---|---|---|---|---|---|
| Statuses | Draft, Pending (review), Scheduled ("future"), Published, Private, Trash | Draft, Changed, Published, Archived | Drafts and published documents | Draft and published content | Draft, In review, Scheduled, Published, Archived; shown to editors as Live / Taken down too |
| Review before publishing | Yes: "Pending" waits for a user who may publish | not checked | not checked | not checked | Yes: users without publishing rights can only save drafts or submit for review |
| Schedule a publish date | Yes | Yes (scheduled actions) | Yes (Scheduled Drafts; Content Releases for groups) | Yes, from the calendar | Yes; goes live by itself |
| Scheduled take-down | Plugin (e.g. WP Unpublish) | Yes (scheduled unpublish) | not checked | not checked | Yes ("Take down on") |
| Calendar or timeline of content | not checked | Scheduled content page | Releases overview | Marketing calendar of scheduled and draft content | Gantt-style Publishing timeline with drag to reschedule |
| Group many changes into one release | not checked | Yes (Releases) | Yes (Content Releases) | Campaigns | No |
| Editing rights per department | not checked | not checked | not checked | not checked | Yes (college_admin edits one college) |

"not checked" means I did not confirm it for this comparison, not that the product lacks it.

## What ULS Academics adopted

- **WordPress-style workflow**: Draft → In review → Scheduled → Published, plus Archived.
  The list and the timeline also show the live state visitors see: *Live* or *Taken down*.
- **Contentful-style take-down date** for announcements that expire (enrollment, deadlines).
- **HubSpot-style calendar**, as a Gantt timeline: one bar per story from publish to
  take-down date, grouped by college, colored by status, events shown alongside.
- **Fail-safe scheduling**: the public site checks the dates itself, so a scheduled story
  appears on time even if the server's scheduler is not running; the scheduler only
  updates the status label in the admin.

## Not adopted (and why)

- **Release bundles** (Contentful Releases, Sanity Content Releases): useful for
  coordinated launches of many pages; ULS publishes single stories. Revisit if a college
  wants to launch a whole program page and its news at one moment.
- **Approval chains with several reviewers**: one review step (anyone with publishing rights)
  fits a university office. Add a second level if the communications office asks for it.
