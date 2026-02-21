<?php
/**
 * Single employee card partial.
 *
 * Variables provided by the caller:
 *   @var WP_User $user
 *   @var array   $profile  Keys: department, job_title, phone, office, bio, photo_url
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$photo          = ! empty( $profile['photo_url'] )
	? esc_url( $profile['photo_url'] )
	: get_avatar_url( $user->ID, [ 'size' => 96 ] );
$full_name = trim( $user->first_name . ' ' . $user->last_name );
if ( '' === $full_name ) {
	$full_name = $user->display_name;
}
?>
<article class="ed-card" aria-label="<?php echo esc_attr( $full_name ); ?>">

	<img
		class="ed-card__photo"
		src="<?php echo $photo; // Already escaped above. ?>"
		alt="<?php echo esc_attr( $full_name ); ?>"
		width="96"
		height="96"
		loading="lazy"
	/>

	<div class="ed-card__info">
		<h3 class="ed-card__name">
			<a href="<?php echo esc_url( employee_dir_get_profile_url( $user ) ); ?>">
				<?php echo esc_html( $full_name ); ?>
			</a>
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
			</a>
		</p>

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
