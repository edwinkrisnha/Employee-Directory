# Changelog

All notable changes to Employee Directory will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
- **Individual profile page** — each employee name on a card links to `/staff/{username}` via a custom WP rewrite rule. The profile page renders inside the active theme's header/footer and shows all fields unconditionally (ignores visible_fields setting).
- **AJAX pagination** — numbered page navigation below the results grid; clicking a page number fetches results without a page reload. Driven by the existing `paged` arg in `WP_User_Query`. Search or filter resets to page 1 automatically.
- **List/grid/vertical view toggle** — two buttons in the filter bar let visitors switch between card grid, compact list, and vertical portrait layout; preference persists in `localStorage`.
- **Department color stripe** — each card gets a 3 px left border color auto-assigned from an 8-color palette based on the department name (deterministic, no admin config needed). Toggleable via settings.
- **Adjustable photo size** — admin setting controls card photo diameter: Small (40 px), Medium (64 px, default), or Large (96 px).
- **LinkedIn URL profile field** — `employee_dir_linkedin_url` meta key; appears as a link on cards and profile pages.
- **Start Date profile field** — `employee_dir_start_date` meta key (YYYY-MM-DD); cards show computed tenure (e.g. "3 yrs"), profile pages show the full formatted date plus tenure.
- **Send message quick action** — admin setting: None (hidden), Email (mailto: link), or Microsoft Teams (`teams.microsoft.com/l/chat` URL using the employee's email). Rendered as a small action button on each card.
- **Photo click → profile page** — clicking the card photo navigates to the employee's full profile page.
- **Copy email icon** — inline copy icon (SVG) next to each employee's email address; clicking it copies the address to the clipboard via the Clipboard API; icon turns green briefly to confirm.
- `employee_dir_get_employee_query()` — new public function that returns the full `WP_User_Query` object (with `count_total => true`) for callers that need both results and total count.
- `employee_dir_get_profile_url( WP_User $user )` — returns the canonical `/staff/{user_nicename}/` URL for a given user.
- `employee_dir_pagination_html( $total_pages, $current_page )` — generates accessible pagination nav HTML with ellipsis compression; used by both the shortcode and the AJAX handler.
- `employee_dir_dept_color( $dept )` — returns a deterministic hex color for a department name.
- `employee_dir_years_at_company( $start_date )` — computes a human-readable tenure string from a YYYY-MM-DD date.
- `register_activation_hook` / `register_deactivation_hook` flush rewrite rules so the `/staff/` URL works immediately after activation.

### Changed
- **Photo fallback** — replaced Gravatar with a [DiceBear](https://www.dicebear.com/) generated avatar (big-smile style, seeded from the employee's name) when no custom photo URL is set.
- `employee_dir_get_employees()` is now a thin wrapper around `employee_dir_get_employee_query()->get_results()` — no breaking change for existing callers.
- AJAX handler now returns `pagination` HTML alongside `html` in the JSON response.
- Settings page under **Settings → Internal Staff Directory** with four configurable options:
  - **Results per page** — replaces the hardcoded limit of 200 (range: 1–500)
  - **User roles to include** — filter which WordPress roles appear in the directory; empty = all roles
  - **Visible card fields** — toggle Department, Job Title, Phone, Office/Location, and Bio independently
  - **Require login to view** — when enabled, guests see a login prompt instead of the directory
- `employee_dir_get_settings()` helper centralises default-merging for all settings consumers
- Employee cards now display **First name + Last name** (from WordPress core profile fields), falling back to `display_name` when either is blank
- `employee_dir_query_args` filter — lets external code modify `WP_User_Query` arguments before the employee query runs
- `employee_dir_settings_defaults` filter — lets external code override plugin setting defaults
- `employee_dir_card_after` action — fires inside each card `<article>` after all built-in fields, enabling custom field injection

### Changed
- `employee_dir_get_employees()` default `per_page` is now driven by the settings value instead of a hardcoded `200`
- Role filter (`role__in`) applied to `WP_User_Query` when roles are configured in settings
- AJAX handler respects the "require login" setting and returns `wp_send_json_error` for unauthenticated requests when the restriction is active
- `$visible_fields` is now resolved once per render loop (in the parent template / AJAX handler) instead of once per card, removing repeated `get_option` calls inside the partial
- `employee_dir_get_departments()` result is cached in a 1-hour transient; cache is invalidated automatically when any user's department field is saved

## [1.0.0] — 2026-02-21

### Added
- `[employee_directory]` shortcode renders a searchable staff directory on any page
- Debounced AJAX search (300 ms) filtered by name and email via `WP_User_Query`
- Department dropdown filter with instant results on change
- Employee profile fields added to the WordPress user edit/profile screen: Department, Job Title, Phone, Office / Location, Bio, Profile Photo URL
- All profile data stored as user meta — no custom tables, no duplication
- Profile photo falls back to Gravatar when no custom URL is set
- Phone numbers rendered as `tel:` links with non-numeric characters stripped
- AJAX endpoint protected by nonce (`employee_dir_search`)
- Assets (CSS + JS) enqueued only on pages containing the shortcode
- Responsive card grid with `aria-live="polite"` for screen reader announcements
- Accessible focus styles and `screen-reader-text` utility class
