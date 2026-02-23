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
				<label for="<?php echo 'start_date' === $key ? 'ed_start_month' : 'ed_' . esc_attr( $key ); ?>">
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
					<?php
					$raw_start  = $profile[ $key ] ?? '';
					$saved_year = $saved_month = '';
					if ( preg_match( '/^(\d{4})-(\d{2})/', $raw_start, $m ) ) {
						$saved_year  = $m[1];
						$saved_month = $m[2];
					}
					$current_year = (int) gmdate( 'Y' );
					$months = [
						'01' => __( 'January',   'internal-staff-directory' ),
						'02' => __( 'February',  'internal-staff-directory' ),
						'03' => __( 'March',     'internal-staff-directory' ),
						'04' => __( 'April',     'internal-staff-directory' ),
						'05' => __( 'May',       'internal-staff-directory' ),
						'06' => __( 'June',      'internal-staff-directory' ),
						'07' => __( 'July',      'internal-staff-directory' ),
						'08' => __( 'August',    'internal-staff-directory' ),
						'09' => __( 'September', 'internal-staff-directory' ),
						'10' => __( 'October',   'internal-staff-directory' ),
						'11' => __( 'November',  'internal-staff-directory' ),
						'12' => __( 'December',  'internal-staff-directory' ),
					];
					?>
					<select name="ed_start_month" id="ed_start_month">
						<option value=""><?php esc_html_e( '— Month —', 'internal-staff-directory' ); ?></option>
						<?php foreach ( $months as $num => $name ) : ?>
							<option value="<?php echo esc_attr( $num ); ?>" <?php selected( $saved_month, $num ); ?>>
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="ed_start_year" id="ed_start_year" style="margin-left:6px;">
						<option value=""><?php esc_html_e( '— Year —', 'internal-staff-directory' ); ?></option>
						<?php for ( $y = $current_year; $y >= employee_dir_start_year_floor(); $y-- ) : ?>
							<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $saved_year, (string) $y ); ?>>
								<?php echo esc_html( $y ); ?>
							</option>
						<?php endfor; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'The month this employee joined the company. Used to show tenure on the directory card.', 'internal-staff-directory' ); ?>
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

	$current_year = (int) gmdate( 'Y' );
	$data         = [];
	foreach ( array_keys( employee_dir_fields() ) as $field ) {
		if ( 'start_date' === $field ) {
			// Assembled from two separate selects; never typed by the user.
			$year  = isset( $_POST['ed_start_year'] )  ? absint( wp_unslash( $_POST['ed_start_year'] ) )  : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$month = isset( $_POST['ed_start_month'] ) ? absint( wp_unslash( $_POST['ed_start_month'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$data['start_date'] = ( $year >= employee_dir_start_year_floor() && $year <= $current_year && $month >= 1 && $month <= 12 )
				? sprintf( '%04d-%02d', $year, $month )
				: '';
		} elseif ( isset( $_POST[ 'ed_' . $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$data[ $field ] = wp_unslash( $_POST[ 'ed_' . $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	employee_dir_save_profile( $user_id, $data );
}
add_action( 'personal_options_update',  'employee_dir_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'employee_dir_save_extra_profile_fields' );
