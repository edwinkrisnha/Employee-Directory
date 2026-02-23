<?php
/**
 * Individual employee profile page.
 *
 * Registers a custom rewrite rule so /staff/{user_nicename} resolves to a
 * full-page profile view rendered inside the active theme's header/footer.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ---------------------------------------------------------------------------
// Rewrite rule + query var
// ---------------------------------------------------------------------------

/**
 * Register the ed_profile query var and the /staff/{slug} rewrite rule.
 * Hooked to 'init' so it runs before rewrite rules are compiled.
 */
function employee_dir_register_rewrite() {
	add_rewrite_tag( '%ed_profile%', '([^/]+)' );
	add_rewrite_rule(
		'^staff/([^/]+)/?$',
		'index.php?ed_profile=$matches[1]',
		'top'
	);

	// Flush once if our rule isn't in the compiled set yet (e.g. after a code
	// update while the plugin was already active). The flag is cleared on flush
	// so this only runs once per missing-rule condition.
	if ( ! get_option( 'employee_dir_rewrite_flushed' ) ) {
		flush_rewrite_rules( false );
		update_option( 'employee_dir_rewrite_flushed', 1 );
	}
}
add_action( 'init', 'employee_dir_register_rewrite' );

// ---------------------------------------------------------------------------
// URL helper
// ---------------------------------------------------------------------------

/**
 * Return the canonical profile URL for a user.
 *
 * @param WP_User $user
 * @return string
 */
function employee_dir_get_profile_url( WP_User $user ) {
	return home_url( '/staff/' . $user->user_nicename . '/' );
}

// ---------------------------------------------------------------------------
// Template redirect
// ---------------------------------------------------------------------------

/**
 * Intercept /staff/{slug} requests and render the profile page template.
 */
function employee_dir_profile_template_redirect() {
	$nicename = get_query_var( 'ed_profile' );

	if ( ! $nicename ) {
		return;
	}

	$user = get_user_by( 'slug', $nicename );

	if ( ! $user ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		get_template_part( '404' );
		exit;
	}

	// Login gate.
	if ( employee_dir_get_settings()['require_login'] && ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( employee_dir_get_profile_url( $user ) ) );
		exit;
	}

	// Enqueue plugin assets so they appear in wp_head().
	wp_enqueue_style(
		'internal-staff-directory',
		EMPLOYEE_DIR_PLUGIN_URL . 'assets/directory.css',
		[],
		EMPLOYEE_DIR_VERSION
	);

	$profile = employee_dir_get_profile( $user->ID );

	get_header();
	include EMPLOYEE_DIR_PLUGIN_DIR . 'templates/profile-page.php';
	get_footer();
	exit;
}
add_action( 'template_redirect', 'employee_dir_profile_template_redirect' );
