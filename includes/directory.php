<?php
/**
 * Shortcode, WP_User_Query wrapper, AJAX handler, and asset enqueueing.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Query employees using WP_User_Query.
 *
 * @param array $args {
 *   @type string $search     Search term matched against name and email.
 *   @type string $department Filter by exact department value.
 *   @type int    $per_page   Number of results. Default 200.
 *   @type int    $paged      Page number. Default 1.
 * }
 * @return WP_User[]
 */
function employee_dir_get_employees( array $args = [] ) {
	$args = wp_parse_args( $args, [
		'search'     => '',
		'department' => '',
		'per_page'   => 200,
		'paged'      => 1,
	] );

	$query_args = [
		'number'  => absint( $args['per_page'] ),
		'paged'   => absint( $args['paged'] ),
		'orderby' => 'display_name',
		'order'   => 'ASC',
	];

	if ( ! empty( $args['search'] ) ) {
		$query_args['search']         = '*' . sanitize_text_field( $args['search'] ) . '*';
		$query_args['search_columns'] = [ 'display_name', 'user_email', 'user_login' ];
	}

	if ( ! empty( $args['department'] ) ) {
		$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
			[
				'key'     => 'employee_dir_department',
				'value'   => sanitize_text_field( $args['department'] ),
				'compare' => '=',
			],
		];
	}

	return ( new WP_User_Query( $query_args ) )->get_results();
}

/**
 * [employee_directory] shortcode.
 * Renders the full directory with search form on page load.
 *
 * @return string HTML output.
 */
function employee_dir_shortcode( $atts ) {
	shortcode_atts( [], $atts, 'employee_directory' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$search     = isset( $_GET['ed_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ed_search'] ) ) : '';
	$department = isset( $_GET['ed_dept'] )   ? sanitize_text_field( wp_unslash( $_GET['ed_dept'] ) )   : '';
	// phpcs:enable

	$employees   = employee_dir_get_employees( compact( 'search', 'department' ) );
	$departments = employee_dir_get_departments();

	ob_start();
	include EMPLOYEE_DIR_PLUGIN_DIR . 'templates/directory.php';
	return ob_get_clean();
}
add_shortcode( 'employee_directory', 'employee_dir_shortcode' );

/**
 * AJAX handler: returns filtered employee card HTML.
 * Used by the JS search/filter UI for instant results without a page reload.
 */
function employee_dir_ajax_search() {
	check_ajax_referer( 'employee_dir_search', 'nonce' );

	$search     = isset( $_POST['search'] )     ? sanitize_text_field( wp_unslash( $_POST['search'] ) )     : '';
	$department = isset( $_POST['department'] ) ? sanitize_text_field( wp_unslash( $_POST['department'] ) ) : '';

	$employees = employee_dir_get_employees( compact( 'search', 'department' ) );

	ob_start();
	foreach ( $employees as $user ) {
		$profile = employee_dir_get_profile( $user->ID );
		include EMPLOYEE_DIR_PLUGIN_DIR . 'templates/profile-card.php';
	}
	$html = ob_get_clean();

	if ( empty( trim( $html ) ) ) {
		$html = '<p class="ed-no-results">' . esc_html__( 'No employees found.', 'employee-directory' ) . '</p>';
	}

	wp_send_json_success( [ 'html' => $html ] );
}
add_action( 'wp_ajax_employee_dir_search',        'employee_dir_ajax_search' );
add_action( 'wp_ajax_nopriv_employee_dir_search', 'employee_dir_ajax_search' );

/**
 * Enqueue front-end assets only on pages that contain the shortcode.
 */
function employee_dir_enqueue_assets() {
	global $post;

	if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'employee_directory' ) ) {
		return;
	}

	wp_enqueue_style(
		'employee-directory',
		EMPLOYEE_DIR_PLUGIN_URL . 'assets/directory.css',
		[],
		EMPLOYEE_DIR_VERSION
	);

	wp_enqueue_script(
		'employee-directory',
		EMPLOYEE_DIR_PLUGIN_URL . 'assets/directory.js',
		[ 'jquery' ],
		EMPLOYEE_DIR_VERSION,
		true
	);

	wp_localize_script( 'employee-directory', 'employeeDir', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'employee_dir_search' ),
		'action'  => 'employee_dir_search',
	] );
}
add_action( 'wp_enqueue_scripts', 'employee_dir_enqueue_assets' );
