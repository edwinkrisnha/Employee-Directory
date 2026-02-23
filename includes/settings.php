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
	/**
	 * Filters the default settings values.
	 *
	 * @param array $defaults {
	 *   @type int      $per_page       Default results per page.
	 *   @type string[] $roles          Default roles to include (empty = all).
	 *   @type string[] $visible_fields Default visible card fields.
	 *   @type int      $require_login  Default login requirement (0 or 1).
	 * }
	 */
	$defaults = apply_filters( 'employee_dir_settings_defaults', [
		'per_page'         => 200,
		'roles'            => [],
		'visible_fields'   => [ 'department', 'job_title', 'phone', 'office', 'bio', 'linkedin_url', 'start_date' ],
		'require_login'    => 0,
		'photo_size'       => 'medium',
		'dept_colors'      => 1,
		'message_platform' => 'none',
		'dicebear_style'   => 'big-smile',
		'new_hire_days'    => 90,
	] );

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

	add_settings_field(
		'employee_dir_photo_size',
		__( 'Profile photo size', 'internal-staff-directory' ),
		'employee_dir_field_photo_size',
		'employee-dir-settings',
		'employee_dir_main'
	);

	add_settings_field(
		'employee_dir_dept_colors',
		__( 'Department color stripe', 'internal-staff-directory' ),
		'employee_dir_field_dept_colors',
		'employee-dir-settings',
		'employee_dir_main'
	);

	add_settings_field(
		'employee_dir_message_platform',
		__( 'Send message platform', 'internal-staff-directory' ),
		'employee_dir_field_message_platform',
		'employee-dir-settings',
		'employee_dir_main'
	);

	add_settings_field(
		'employee_dir_dicebear_style',
		__( 'Avatar fallback style', 'internal-staff-directory' ),
		'employee_dir_field_dicebear_style',
		'employee-dir-settings',
		'employee_dir_main'
	);

	add_settings_field(
		'employee_dir_new_hire_days',
		__( '"New" badge window', 'internal-staff-directory' ),
		'employee_dir_field_new_hire_days',
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
	$allowed_fields          = [ 'department', 'job_title', 'phone', 'office', 'bio', 'linkedin_url', 'start_date' ];
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

	// photo_size: whitelist
	$valid_sizes = [ 'small', 'medium', 'large' ];
	$output['photo_size'] = ( isset( $input['photo_size'] ) && in_array( $input['photo_size'], $valid_sizes, true ) )
		? $input['photo_size']
		: 'medium';

	// dept_colors: boolean stored as int
	$output['dept_colors'] = ! empty( $input['dept_colors'] ) ? 1 : 0;

	// message_platform: whitelist
	$valid_platforms = [ 'none', 'mailto', 'teams' ];
	$output['message_platform'] = ( isset( $input['message_platform'] ) && in_array( $input['message_platform'], $valid_platforms, true ) )
		? $input['message_platform']
		: 'none';

	// dicebear_style: whitelist against all valid DiceBear v9 style slugs
	$valid_dicebear_styles = [
		'adventurer', 'adventurer-neutral', 'avataaars', 'avataaars-neutral',
		'big-ears', 'big-ears-neutral', 'big-smile', 'bottts', 'bottts-neutral',
		'croodles', 'croodles-neutral', 'dylan', 'fun-emoji', 'glass', 'icons',
		'identicon', 'initials', 'lorelei', 'lorelei-neutral', 'micah', 'miniavs',
		'notionists', 'notionists-neutral', 'open-peeps', 'personas', 'pixel-art',
		'pixel-art-neutral', 'rings', 'shapes', 'thumbs', 'toon-head',
	];
	$output['dicebear_style'] = ( isset( $input['dicebear_style'] ) && in_array( $input['dicebear_style'], $valid_dicebear_styles, true ) )
		? $input['dicebear_style']
		: 'big-smile';

	// new_hire_days: integer 0–365 (0 = feature disabled)
	if ( isset( $input['new_hire_days'] ) ) {
		$output['new_hire_days'] = min( 365, absint( $input['new_hire_days'] ) );
	}

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
		'department'   => __( 'Department', 'internal-staff-directory' ),
		'job_title'    => __( 'Job Title', 'internal-staff-directory' ),
		'phone'        => __( 'Phone', 'internal-staff-directory' ),
		'office'       => __( 'Office / Location', 'internal-staff-directory' ),
		'bio'          => __( 'Bio', 'internal-staff-directory' ),
		'linkedin_url' => __( 'LinkedIn URL', 'internal-staff-directory' ),
		'start_date'   => __( 'Start Date / Years at company', 'internal-staff-directory' ),
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

/**
 * Render the "Profile photo size" radio group.
 */
function employee_dir_field_photo_size() {
	$settings = employee_dir_get_settings();
	$current  = $settings['photo_size'];
	$options  = [
		'small'  => __( 'Small (40 px)', 'internal-staff-directory' ),
		'medium' => __( 'Medium (64 px)', 'internal-staff-directory' ),
		'large'  => __( 'Large (96 px)', 'internal-staff-directory' ),
	];
	foreach ( $options as $value => $label ) : ?>
		<label style="display:inline-block; margin-right: 1rem;">
			<input
				type="radio"
				name="employee_dir_settings[photo_size]"
				value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $current, $value ); ?>
			/>
			<?php echo esc_html( $label ); ?>
		</label>
	<?php endforeach;
}

/**
 * Render the "Department color stripe" checkbox.
 */
function employee_dir_field_dept_colors() {
	$settings = employee_dir_get_settings();
	?>
	<label>
		<input
			type="checkbox"
			name="employee_dir_settings[dept_colors]"
			value="1"
			<?php checked( 1, $settings['dept_colors'] ); ?>
		/>
		<?php esc_html_e( 'Color-code cards by department (auto-assigned)', 'internal-staff-directory' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Adds a colored left border to each card based on the employee\'s department.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

/**
 * Render the "Send message platform" select.
 */
function employee_dir_field_message_platform() {
	$settings = employee_dir_get_settings();
	$options  = [
		'none'   => __( 'None (hide button)', 'internal-staff-directory' ),
		'mailto' => __( 'Email (opens email client)', 'internal-staff-directory' ),
		'teams'  => __( 'Microsoft Teams', 'internal-staff-directory' ),
	];
	?>
	<select name="employee_dir_settings[message_platform]" id="employee_dir_message_platform">
		<?php foreach ( $options as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['message_platform'], $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e( 'Shows a quick-action button on each employee card to start a conversation.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

/**
 * Render the "Avatar fallback style" select (DiceBear v9 styles).
 */
function employee_dir_field_dicebear_style() {
	$settings = employee_dir_get_settings();
	$current  = $settings['dicebear_style'];

	$groups = [
		__( 'Minimalist', 'internal-staff-directory' ) => [
			'glass'      => 'Glass',
			'icons'      => 'Icons',
			'identicon'  => 'Identicon',
			'initials'   => 'Initials',
			'rings'      => 'Rings',
			'shapes'     => 'Shapes',
			'thumbs'     => 'Thumbs',
		],
		__( 'Characters', 'internal-staff-directory' ) => [
			'adventurer'          => 'Adventurer',
			'adventurer-neutral'  => 'Adventurer Neutral',
			'avataaars'           => 'Avataaars',
			'avataaars-neutral'   => 'Avataaars Neutral',
			'big-ears'            => 'Big Ears',
			'big-ears-neutral'    => 'Big Ears Neutral',
			'big-smile'           => 'Big Smile',
			'bottts'              => 'Bottts',
			'bottts-neutral'      => 'Bottts Neutral',
			'croodles'            => 'Croodles',
			'croodles-neutral'    => 'Croodles Neutral',
			'dylan'               => 'Dylan',
			'fun-emoji'           => 'Fun Emoji',
			'lorelei'             => 'Lorelei',
			'lorelei-neutral'     => 'Lorelei Neutral',
			'micah'               => 'Micah',
			'miniavs'             => 'Miniavs',
			'notionists'          => 'Notionists',
			'notionists-neutral'  => 'Notionists Neutral',
			'open-peeps'          => 'Open Peeps',
			'personas'            => 'Personas',
			'pixel-art'           => 'Pixel Art',
			'pixel-art-neutral'   => 'Pixel Art Neutral',
			'toon-head'           => 'Toon Head',
		],
	];
	?>
	<select name="employee_dir_settings[dicebear_style]" id="employee_dir_dicebear_style">
		<?php foreach ( $groups as $group_label => $styles ) : ?>
			<optgroup label="<?php echo esc_attr( $group_label ); ?>">
				<?php foreach ( $styles as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</optgroup>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php
		printf(
			/* translators: %s: URL to DiceBear style previews */
			esc_html__( 'Style used for generated avatars when an employee has no profile photo set. %s', 'internal-staff-directory' ),
			'<a href="https://www.dicebear.com/styles/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Preview all styles', 'internal-staff-directory' ) . '</a>'
		);
		?>
	</p>
	<?php
}

/**
 * Render the '"New" badge window' number input.
 */
function employee_dir_field_new_hire_days() {
	$settings = employee_dir_get_settings();
	?>
	<input
		type="number"
		id="employee_dir_new_hire_days"
		name="employee_dir_settings[new_hire_days]"
		value="<?php echo esc_attr( $settings['new_hire_days'] ); ?>"
		min="0"
		max="365"
		class="small-text"
	/>
	<p class="description">
		<?php esc_html_e( 'Employees who joined within this many days get a "New" badge on their card. Set to 0 to disable.', 'internal-staff-directory' ); ?>
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
