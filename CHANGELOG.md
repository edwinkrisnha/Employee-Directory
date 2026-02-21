# Changelog

All notable changes to Employee Directory will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

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
