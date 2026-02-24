<?php
/**
 * Single employee card partial.
 *
 * Variables provided by the caller:
 *   @var WP_User  $user
 *   @var array    $profile        Keys: department, job_title, phone, office, bio, photo_url,
 *                                        linkedin_url, start_date
 *   @var string[] $visible_fields Fields enabled in plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$settings         = employee_dir_get_settings();
$photo_size_map   = [ 'small' => 40, 'medium' => 64, 'large' => 96 ];
$photo_px         = $photo_size_map[ $settings['photo_size'] ] ?? 64;
$dept_color       = $settings['dept_colors'] ? employee_dir_dept_color( $profile['department'] ?? '' ) : '';
$message_platform = $settings['message_platform'];
$profile_url      = employee_dir_get_profile_url( $user );

$full_name = trim( $user->first_name . ' ' . $user->last_name );
if ( '' === $full_name ) {
	$full_name = $user->display_name;
}

$photo = employee_dir_get_avatar_url( $user, $photo_px );

$article_style = $dept_color ? ' style="--ed-dept-color:' . esc_attr( $dept_color ) . ';"' : '';

$is_new_hire   = false;
$new_hire_days = absint( $settings['new_hire_days'] );
if ( $new_hire_days > 0 && ! empty( $profile['start_date'] ) ) {
	$raw_date = $profile['start_date'];
	if ( preg_match( '/^\d{4}-\d{2}$/', $raw_date ) ) {
		$raw_date .= '-01';
	}
	try {
		$days_since  = ( new DateTime( $raw_date ) )->diff( new DateTime( 'today' ) )->days;
		$is_new_hire = ( $days_since <= $new_hire_days );
	} catch ( Exception $e ) {} // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
}
?>
<article class="ed-card" aria-label="<?php echo esc_attr( $full_name ); ?>"<?php echo $article_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr above. ?>>

	<a href="<?php echo esc_url( $profile_url ); ?>" tabindex="-1" aria-hidden="true">
		<img
			class="ed-card__photo ed-card__photo--<?php echo esc_attr( $settings['photo_size'] ); ?>"
			src="<?php echo $photo; // Already escaped above. ?>"
			alt="<?php echo esc_attr( $full_name ); ?>"
			width="<?php echo esc_attr( $photo_px ); ?>"
			height="<?php echo esc_attr( $photo_px ); ?>"
			loading="lazy"
		/>
	</a>

	<div class="ed-card__info">
		<h3 class="ed-card__name">
			<a href="<?php echo esc_url( $profile_url ); ?>">
				<?php echo esc_html( $full_name ); ?>
			</a>
			<?php if ( $is_new_hire ) : ?>
				<span class="ed-card__new-badge" aria-label="<?php esc_attr_e( 'New hire', 'internal-staff-directory' ); ?>">
					<?php esc_html_e( 'New', 'internal-staff-directory' ); ?>
				</span>
			<?php endif; ?>
		</h3>

		<?php if ( ! empty( $profile['job_title'] ) && in_array( 'job_title', $visible_fields, true ) ) : ?>
			<p class="ed-card__title"><?php echo esc_html( $profile['job_title'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $profile['department'] ) && in_array( 'department', $visible_fields, true ) ) : ?>
			<p class="ed-card__dept"><?php echo esc_html( $profile['department'] ); ?></p>
		<?php endif; ?>

		<p class="ed-card__email">
			<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>">
				<?php echo esc_html( $user->user_email ); ?>
			</a><span
				class="ed-copy-email"
				data-email="<?php echo esc_attr( $user->user_email ); ?>"
			><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg></span>
		</p>

		<?php if ( 'none' !== $message_platform ) : ?>
			<?php if ( 'mailto' === $message_platform ) : ?>
				<a
					href="mailto:<?php echo esc_attr( $user->user_email ); ?>"
					class="ed-card__action-btn"
				><?php esc_html_e( 'Message', 'internal-staff-directory' ); ?></a>
			<?php elseif ( 'teams' === $message_platform ) : ?>
				<a
					href="https://teams.microsoft.com/l/chat/0/0?users=<?php echo esc_attr( $user->user_email ); ?>"
					target="_blank"
					rel="noopener noreferrer"
					class="ed-card__action-btn"
				><?php esc_html_e( 'Teams', 'internal-staff-directory' ); ?></a>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( ! empty( $profile['phone'] ) && in_array( 'phone', $visible_fields, true ) ) : ?>
			<p class="ed-card__phone">
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $profile['phone'] ) ); ?>">
					<?php echo esc_html( $profile['phone'] ); ?>
				</a>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $profile['office'] ) && in_array( 'office', $visible_fields, true ) ) : ?>
			<p class="ed-card__office"><?php echo esc_html( $profile['office'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $profile['bio'] ) && in_array( 'bio', $visible_fields, true ) ) : ?>
			<p class="ed-card__bio"><?php echo esc_html( $profile['bio'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $profile['linkedin_url'] ) && in_array( 'linkedin_url', $visible_fields, true ) ) : ?>
			<p class="ed-card__social">
				<a href="<?php echo esc_url( $profile['linkedin_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'LinkedIn', 'internal-staff-directory' ); ?>
				</a>
			</p>
		<?php endif; ?>

		<?php
		$tenure = ! empty( $profile['start_date'] ) ? employee_dir_years_at_company( $profile['start_date'] ) : '';
		if ( $tenure && in_array( 'start_date', $visible_fields, true ) ) :
		?>
			<p class="ed-card__tenure"><?php echo esc_html( $tenure ); ?></p>
		<?php endif; ?>
	</div>

	<?php
	/**
	 * Fires after the employee card content, inside the <article> element.
	 *
	 * @param WP_User $user    The employee user object.
	 * @param array   $profile The employee profile meta array.
	 */
	do_action( 'employee_dir_card_after', $user, $profile );
	?>

</article>
