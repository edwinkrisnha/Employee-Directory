<?php
/**
 * Employee profile field definitions and user meta CRUD.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns the canonical list of employee profile fields.
 * Single source of truth — used by admin forms, front-end display, and CRUD.
 *
 * @return array<string, string> Field key => label.
 */
function employee_dir_fields() {
	return [
		'department' => __( 'Department', 'internal-staff-directory' ),
		'job_title'  => __( 'Job Title', 'internal-staff-directory' ),
		'phone'      => __( 'Phone', 'internal-staff-directory' ),
		'office'     => __( 'Office / Location', 'internal-staff-directory' ),
		'bio'        => __( 'Bio', 'internal-staff-directory' ),
		'photo_url'  => __( 'Profile Photo URL', 'internal-staff-directory' ),
	];
}

/**
 * Get all employee profile fields for a user.
 *
 * @param int $user_id
 * @return array<string, string> Keyed by field name.
 */
function employee_dir_get_profile( $user_id ) {
	$profile = [];
	foreach ( array_keys( employee_dir_fields() ) as $field ) {
		$profile[ $field ] = (string) get_user_meta( $user_id, 'employee_dir_' . $field, true );
	}
	return $profile;
}

/**
 * Save employee profile fields for a user.
 * All sanitization happens here — callers pass raw input.
 *
 * @param int   $user_id
 * @param array $data Raw input data.
 */
function employee_dir_save_profile( $user_id, array $data ) {
	$sanitizers = [
		'department' => 'sanitize_text_field',
		'job_title'  => 'sanitize_text_field',
		'phone'      => 'sanitize_text_field',
		'office'     => 'sanitize_text_field',
		'bio'        => 'sanitize_textarea_field',
		'photo_url'  => 'esc_url_raw',
	];

	foreach ( $sanitizers as $field => $sanitizer ) {
		if ( array_key_exists( $field, $data ) ) {
			update_user_meta( $user_id, 'employee_dir_' . $field, $sanitizer( $data[ $field ] ) );
		}
	}

	// Bust the departments cache whenever any profile is saved — department
	// values may have been added, changed, or removed.
	if ( array_key_exists( 'department', $data ) ) {
		delete_transient( 'employee_dir_departments' );
	}
}

/**
 * Get all unique, non-empty department values across all users.
 *
 * @return string[]
 */
function employee_dir_get_departments() {
	$cached = get_transient( 'employee_dir_departments' );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_col(
		"SELECT DISTINCT meta_value
		 FROM {$wpdb->usermeta}
		 WHERE meta_key = 'employee_dir_department'
		   AND meta_value != ''
		 ORDER BY meta_value ASC"
	);

	$departments = $rows ?: [];
	set_transient( 'employee_dir_departments', $departments, HOUR_IN_SECONDS );

	return $departments;
}
