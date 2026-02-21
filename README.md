# Internal Staff Directory

A lightweight WordPress plugin for internal staff directories. Provides a searchable, filterable employee directory with AJAX-powered search, department filtering, and extended user profile fields.

## Features

- **Searchable directory** – Search employees by name, email, or username
- **Department filtering** – Instant filtering by department with no page reload
- **AJAX-powered** – Debounced search and live filtering without full page refreshes
- **Extended user profiles** – Adds Department, Job Title, Phone, Office/Location, Bio, and Photo URL fields to WordPress user profiles
- **Photo fallback** – Uses custom photo URLs or falls back to Gravatar automatically
- **Responsive card grid** – Clean card layout that adapts to all screen sizes
- **Accessibility-first** – ARIA labels, screen reader text, and live regions for dynamic updates
- **Conditional asset loading** – CSS and JS only load on pages that use the shortcode
- **Admin settings page** – Configure results per page, visible card fields, included roles, and login requirement from **Settings → Internal Staff Directory**

## Requirements

- WordPress 5.0+
- PHP 5.6+
- jQuery (bundled with WordPress)

## Installation

1. Upload the `internal-staff-directory` folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins** in the WordPress admin
3. Add the shortcode to any page or post

## Usage

### Shortcode

Place the directory on any page or post:

```
[employee_directory]
```

No attributes are required. The shortcode renders the full searchable directory with department filter.

### Settings

Go to **Settings → Internal Staff Directory** to configure:

| Setting | Default | Description |
|---|---|---|
| Results per page | 200 | Maximum employees shown (1–500) |
| User roles to include | All roles | Restrict the directory to specific WordPress roles |
| Visible card fields | All fields | Show or hide Department, Job Title, Phone, Office/Location, Bio |
| Require login to view | Off | When on, guests see a login prompt instead of the directory |

### Profile Fields

Each WordPress user gains six additional fields on their profile page (under **Profile** in the admin, or the standard user edit screen):

| Field | Meta Key | Description |
|---|---|---|
| Department | `employee_dir_department` | Team or department name |
| Job Title | `employee_dir_job_title` | Role or position |
| Phone | `employee_dir_phone` | Contact phone number |
| Office / Location | `employee_dir_office` | Physical office or remote location |
| Bio | `employee_dir_bio` | Short biography |
| Profile Photo URL | `employee_dir_photo_url` | Direct URL to a profile photo |

Users with the `edit_user` capability can edit these fields. Employees can update their own fields from the **Profile** screen.

## Data Storage

No custom database tables are created. All employee data is stored in the native WordPress `wp_usermeta` table using the `employee_dir_` prefix.

## Hooks

### Actions

| Hook | Description |
|---|---|
| `wp_ajax_employee_dir_search` | AJAX search handler for logged-in users |
| `wp_ajax_nopriv_employee_dir_search` | AJAX search handler for public visitors |
| `show_user_profile` | Renders profile fields on the user's own profile page |
| `edit_user_profile` | Renders profile fields on the admin user edit page |
| `personal_options_update` | Saves fields when a user updates their own profile |
| `edit_user_profile_update` | Saves fields when an admin updates a user profile |
| `employee_dir_card_after` | Fires inside each card `<article>` after all built-in fields — use to inject custom content. Receives `WP_User $user, array $profile`. |

### Filters

| Hook | Description |
|---|---|
| `employee_dir_query_args` | Modify `WP_User_Query` arguments before the employee query runs. Receives `array $query_args, array $args`. |
| `employee_dir_settings_defaults` | Override plugin setting defaults. Receives `array $defaults`. |

## File Structure

```
internal-staff-directory/
├── internal-staff-directory.php    # Main plugin file — registers hooks and loads includes
├── includes/
│   ├── profile.php           # User meta read/write (field definitions, getters, savers)
│   ├── settings.php          # Admin settings page, Settings API registration, sanitizers
│   ├── directory.php         # Shortcode, WP_User_Query logic, AJAX handler
│   └── admin.php             # Admin profile field UI (render and save)
├── templates/
│   ├── directory.php         # Shortcode output markup (search, filter, results wrapper)
│   └── profile-card.php      # Individual employee card partial
├── assets/
│   ├── directory.css         # Responsive card grid and component styles
│   └── directory.js          # Debounced search, department filter, AJAX, DOM update
├── CHANGELOG.md
├── LICENSE
└── README.md
```

## Security

- All AJAX requests are validated with WordPress nonces (`check_ajax_referer`)
- User input is sanitized before use in queries (`sanitize_text_field`)
- All output is escaped before rendering (`esc_html`, `esc_url`, `esc_attr`)
- Phone numbers are filtered to digits and `+` only before storage

## License

[GPL-2.0-or-later](LICENSE)