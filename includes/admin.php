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
				<?php elseif ( 'photo_url' === $key ) : ?>
					<?php employee_dir_admin_render_photo_field( $profile[ $key ] ?? '' ); ?>
				<?php elseif ( 'linkedin_url' === $key ) : ?>
					<input
						type="url"
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $profile[ $key ] ?? '' ); ?>"
						class="regular-text"
						placeholder="https://"
					/>
					<p class="description">
						<?php esc_html_e( 'e.g. https://linkedin.com/in/yourname', 'internal-staff-directory' ); ?>
					</p>
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

// ---------------------------------------------------------------------------
// Media library uploader for profile photo
// ---------------------------------------------------------------------------

/**
 * Render the profile photo input with a media library picker button and live preview.
 * Used on both the WP admin profile screen and the HR Staff tab.
 *
 * @param string $value Current photo URL (may be empty).
 */
function employee_dir_admin_render_photo_field( $value ) {
	$has_photo = '' !== (string) $value;
	?>
	<input
		type="url"
		id="ed_photo_url"
		name="ed_photo_url"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
		placeholder="https://"
	/>
	<button type="button" class="button" id="ed-photo-select" style="margin-left:4px;">
		<?php esc_html_e( 'Select Photo', 'internal-staff-directory' ); ?>
	</button>
	<button type="button" class="button-link" id="ed-photo-remove"
		style="margin-left:8px;color:#a00;<?php echo $has_photo ? '' : 'display:none;'; ?>">
		<?php esc_html_e( 'Remove', 'internal-staff-directory' ); ?>
	</button>
	<br>
	<img
		id="ed-photo-preview"
		src="<?php echo esc_url( $value ); ?>"
		alt=""
		style="margin-top:8px;width:80px;height:80px;border-radius:4px;object-fit:cover;<?php echo $has_photo ? '' : 'display:none;'; ?>"
	>
	<p class="description">
		<?php esc_html_e( 'Select a photo from the media library — you will be prompted to crop it to a square. Leave blank to use the generated avatar.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

/**
 * Returns the inline JS for the wp.media photo uploader with built-in square crop.
 * Opens the media library, then transitions to WP's native Cropper state (1:1 ratio).
 * Shared between the admin profile screen and the HR Staff tab.
 *
 * @return string
 */
function employee_dir_admin_photo_js() {
	return "jQuery(function($){
	function setPhoto(url){
		$('#ed_photo_url').val(url);
		$('#ed-photo-preview').attr('src',url).show();
		$('#ed-photo-remove').show();
	}
	$(document).on('click','#ed-photo-select',function(e){
		e.preventDefault();
		var saved=null;
		var frame=wp.media({
			button:{text:'Crop & Select',close:false},
			states:[
				new wp.media.controller.Library({
					title:'Select Profile Photo',
					library:wp.media.query({type:'image'}),
					multiple:false,
					date:false,
				}),
				new wp.media.controller.Cropper({
					canSkipCrop:true,
					suggestedWidth:400,
					suggestedHeight:400,
					control:{params:{
						flex_width:false,
						flex_height:false,
						width:400,
						height:400,
					}},
				}),
			]
		});
		frame.on('select',function(){
			saved=frame.state().get('selection').first().toJSON();
			frame.setState('cropper');
		});
		frame.on('cropped',function(croppedImage){
			setPhoto(croppedImage.url);
			frame.close();
		});
		frame.on('skipped',function(){
			if(saved){setPhoto(saved.url);}
			frame.close();
		});
		frame.open();
	});
	$(document).on('click','#ed-photo-remove',function(e){
		e.preventDefault();
		$('#ed_photo_url').val('');
		$('#ed-photo-preview').attr('src','').hide();
		$(this).hide();
	});
});";
}

/**
 * Enqueue wp.media and the uploader JS on the user profile / user-edit screen.
 *
 * @param string $hook Current admin page hook.
 */
function employee_dir_admin_enqueue_media( $hook ) {
	if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', employee_dir_admin_photo_js() );
}
add_action( 'admin_enqueue_scripts', 'employee_dir_admin_enqueue_media' );
