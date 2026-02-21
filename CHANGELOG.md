# Changelog

All notable changes to Employee Directory will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
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
