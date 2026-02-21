<?php
/**
 * Settings page for Internal Staff Directory.
 *
 * Registers a sub-page under Settings → Internal Staff Directory and stores
 * all plugin configuration in a single WP option: employee_dir_settings.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

/**
 * Return plugin settings merged with defaults.
 *
 * @return array{per_page: int, roles: string[], visible_fields: string[], require_login: int}
 */
function employee_dir_get_settings() {
	$defaults = [
		'per_page'       => 200,
		'roles'          => [],
		'visible_fields' => [ 'department', 'job_title', 'phone', 'office', 'bio' ],
		'require_login'  => 0,
	];

	$saved = get_option( 'employee_dir_settings', [] );

	return wp_parse_args( $saved, $defaults );
}

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------

/**
 * Register the Settings → Internal Staff Directory sub-page.
 */
function employee_dir_add_settings_page() {
	add_options_page(
		__( 'Internal Staff Directory Settings', 'internal-staff-directory' ),
		__( 'Internal Staff Directory', 'internal-staff-directory' ),
		'manage_options',
		'employee-dir-settings',
		'employee_dir_render_settings_page'
	);
}
add_action( 'admin_menu', 'employee_dir_add_settings_page' );

// ---------------------------------------------------------------------------
// Settings registration
// ---------------------------------------------------------------------------

/**
 * Register setting, section, and fields via the WordPress Settings API.
 */
function employee_dir_register_settings() {
	register_setting(
		'employee_dir',
		'employee_dir_settings',
		[ 'sanitize_callback' => 'employee_dir_sanitize_settings' ]
	);

	add_settings_section(
		'employee_dir_main',
		__( 'Directory Settings', 'internal-staff-directory' ),
		'__return_false',
		'employee-dir-settings'
	);

	add_settings_field(
		'employee_dir_per_page',
		__( 'Results per page', 'internal-staff-directory' ),
		'employee_dir_field_per_page',
		'employee-dir-settings',
		'employee_dir_main'
	);

	add_settings_field(
		'employee_dir_roles',
		__( 'User roles to include', 'internal-staff-directory' ),
		'employee_dir_field_roles',
		'employee-dir-settings',
		'employee_dir_main'
	);

	add_settings_field(
		'employee_dir_visible_fields',
		__( 'Visible card fields', 'internal-staff-directory' ),
		'employee_dir_field_visible_fields',
		'employee-dir-settings',
		'employee_dir_main'
	);

	add_settings_field(
		'employee_dir_require_login',
		__( 'Require login to view', 'internal-staff-directory' ),
		'employee_dir_field_require_login',
		'employee-dir-settings',
		'employee_dir_main'
	);
}
add_action( 'admin_init', 'employee_dir_register_settings' );

// ---------------------------------------------------------------------------
// Sanitize callback
// ---------------------------------------------------------------------------

/**
 * Validate and sanitize all settings before saving.
 *
 * @param array $input Raw POST data.
 * @return array Sanitized settings.
 */
function employee_dir_sanitize_settings( $input ) {
	$output = employee_dir_get_settings(); // start from current values

	// per_page: integer 1–500
	if ( isset( $input['per_page'] ) ) {
		$per_page          = absint( $input['per_page'] );
		$output['per_page'] = max( 1, min( 500, $per_page ) );
	}

	// roles: whitelist against actual WP roles
	$valid_roles    = array_keys( wp_roles()->roles );
	$output['roles'] = [];
	if ( ! empty( $input['roles'] ) && is_array( $input['roles'] ) ) {
		foreach ( $input['roles'] as $role ) {
			if ( in_array( $role, $valid_roles, true ) ) {
				$output['roles'][] = $role;
			}
		}
	}

	// visible_fields: whitelist against known text fields
	$allowed_fields          = [ 'department', 'job_title', 'phone', 'office', 'bio' ];
	$output['visible_fields'] = [];
	if ( ! empty( $input['visible_fields'] ) && is_array( $input['visible_fields'] ) ) {
		foreach ( $input['visible_fields'] as $field ) {
			if ( in_array( $field, $allowed_fields, true ) ) {
				$output['visible_fields'][] = $field;
			}
		}
	}

	// require_login: boolean stored as int
	$output['require_login'] = ! empty( $input['require_login'] ) ? 1 : 0;

	return $output;
}

// ---------------------------------------------------------------------------
// Field renderers
// ---------------------------------------------------------------------------

/**
 * Render the "Results per page" number input.
 */
function employee_dir_field_per_page() {
	$settings = employee_dir_get_settings();
	?>
	<input
		type="number"
		id="employee_dir_per_page"
		name="employee_dir_settings[per_page]"
		value="<?php echo esc_attr( $settings['per_page'] ); ?>"
		min="1"
		max="500"
		class="small-text"
	/>
	<p class="description">
		<?php esc_html_e( 'Maximum number of employees shown per page (1–500). Default: 200.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

/**
 * Render the "User roles to include" checkbox list.
 */
function employee_dir_field_roles() {
	$settings    = employee_dir_get_settings();
	$saved_roles = $settings['roles'];
	$all_roles   = wp_roles()->roles;
	?>
	<fieldset>
		<legend class="screen-reader-text">
			<?php esc_html_e( 'User roles to include', 'internal-staff-directory' ); ?>
		</legend>
		<?php foreach ( $all_roles as $slug => $role ) : ?>
			<label style="display:block; margin-bottom: 4px;">
				<input
					type="checkbox"
					name="employee_dir_settings[roles][]"
					value="<?php echo esc_attr( $slug ); ?>"
					<?php checked( in_array( $slug, $saved_roles, true ) ); ?>
				/>
				<?php echo esc_html( translate_user_role( $role['name'] ) ); ?>
			</label>
		<?php endforeach; ?>
	</fieldset>
	<p class="description">
		<?php esc_html_e( 'Which roles appear in the directory. Leave all unchecked to include every role.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

/**
 * Render the "Visible card fields" checkbox list.
 */
function employee_dir_field_visible_fields() {
	$settings       = employee_dir_get_settings();
	$visible        = $settings['visible_fields'];
	$allowed_fields = [
		'department' => __( 'Department', 'internal-staff-directory' ),
		'job_title'  => __( 'Job Title', 'internal-staff-directory' ),
		'phone'      => __( 'Phone', 'internal-staff-directory' ),
		'office'     => __( 'Office / Location', 'internal-staff-directory' ),
		'bio'        => __( 'Bio', 'internal-staff-directory' ),
	];
	?>
	<fieldset>
		<legend class="screen-reader-text">
			<?php esc_html_e( 'Visible card fields', 'internal-staff-directory' ); ?>
		</legend>
		<?php foreach ( $allowed_fields as $key => $label ) : ?>
			<label style="display:block; margin-bottom: 4px;">
				<input
					type="checkbox"
					name="employee_dir_settings[visible_fields][]"
					value="<?php echo esc_attr( $key ); ?>"
					<?php checked( in_array( $key, $visible, true ) ); ?>
				/>
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach; ?>
	</fieldset>
	<p class="description">
		<?php esc_html_e( 'Which fields are shown on employee cards. Name, email, and photo are always visible.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

/**
 * Render the "Require login to view" checkbox.
 */
function employee_dir_field_require_login() {
	$settings = employee_dir_get_settings();
	?>
	<label>
		<input
			type="checkbox"
			id="employee_dir_require_login"
			name="employee_dir_settings[require_login]"
			value="1"
			<?php checked( 1, $settings['require_login'] ); ?>
		/>
		<?php esc_html_e( 'Only logged-in users can view the directory', 'internal-staff-directory' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'When enabled, guests see a login prompt instead of the directory.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

// ---------------------------------------------------------------------------
// Page renderer
// ---------------------------------------------------------------------------

/**
 * Render the full settings page.
 */
function employee_dir_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'employee_dir' );
			do_settings_sections( 'employee-dir-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
