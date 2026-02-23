<?php
/**
 * Employee Directory fields on the WordPress user edit/profile screen.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render employee profile fields on the user edit screen.
 *
 * @param WP_User $user
 */
function employee_dir_show_extra_profile_fields( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	$profile = employee_dir_get_profile( $user->ID );
	$fields  = employee_dir_fields();
	?>
	<h2><?php esc_html_e( 'Employee Directory', 'internal-staff-directory' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php foreach ( $fields as $key => $label ) : ?>
		<tr>
			<th scope="row">
				<label for="ed_<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $label ); ?>
				</label>
			</th>
			<td>
				<?php if ( 'bio' === $key ) : ?>
					<textarea
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						rows="4"
						class="large-text"
					><?php echo esc_textarea( $profile[ $key ] ?? '' ); ?></textarea>
				<?php elseif ( 'photo_url' === $key || 'linkedin_url' === $key ) : ?>
					<input
						type="url"
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $profile[ $key ] ?? '' ); ?>"
						class="regular-text"
						placeholder="https://"
					/>
					<?php if ( 'photo_url' === $key ) : ?>
						<p class="description">
							<?php esc_html_e( 'Direct URL to profile photo. Leave blank to use Gravatar.', 'internal-staff-directory' ); ?>
						</p>
					<?php else : ?>
						<p class="description">
							<?php esc_html_e( 'e.g. https://linkedin.com/in/yourname', 'internal-staff-directory' ); ?>
						</p>
					<?php endif; ?>
				<?php elseif ( 'start_date' === $key ) : ?>
					<input
						type="date"
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $profile[ $key ] ?? '' ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'The date this employee joined the company. Used to show tenure on the directory card.', 'internal-staff-directory' ); ?>
					</p>
				<?php else : ?>
					<input
						type="text"
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $profile[ $key ] ?? '' ); ?>"
						class="regular-text"
					/>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'employee_dir_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'employee_dir_show_extra_profile_fields' );

/**
 * Save employee profile fields when a user profile is updated.
 *
 * @param int $user_id
 */
function employee_dir_save_extra_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	$data = [];
	foreach ( array_keys( employee_dir_fields() ) as $field ) {
		if ( isset( $_POST[ 'ed_' . $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$data[ $field ] = wp_unslash( $_POST[ 'ed_' . $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	employee_dir_save_profile( $user_id, $data );
}
add_action( 'personal_options_update',  'employee_dir_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'employee_dir_save_extra_profile_fields' );
