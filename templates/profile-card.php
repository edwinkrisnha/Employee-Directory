<?php
/**
 * Single employee card partial.
 *
 * Variables provided by the caller:
 *   @var WP_User $user
 *   @var array   $profile  Keys: department, job_title, phone, office, bio, photo_url
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$photo = ! empty( $profile['photo_url'] )
	? esc_url( $profile['photo_url'] )
	: get_avatar_url( $user->ID, [ 'size' => 96 ] );
?>
<article class="ed-card" aria-label="<?php echo esc_attr( $user->display_name ); ?>">

	<img
		class="ed-card__photo"
		src="<?php echo $photo; // Already escaped above. ?>"
		alt="<?php echo esc_attr( $user->display_name ); ?>"
		width="96"
		height="96"
		loading="lazy"
	/>

	<div class="ed-card__info">
		<h3 class="ed-card__name"><?php echo esc_html( $user->display_name ); ?></h3>

		<?php if ( ! empty( $profile['job_title'] ) ) : ?>
			<p class="ed-card__title"><?php echo esc_html( $profile['job_title'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $profile['department'] ) ) : ?>
			<p class="ed-card__dept"><?php echo esc_html( $profile['department'] ); ?></p>
		<?php endif; ?>

		<p class="ed-card__email">
			<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>">
				<?php echo esc_html( $user->user_email ); ?>
			</a>
		</p>

		<?php if ( ! empty( $profile['phone'] ) ) : ?>
			<p class="ed-card__phone">
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $profile['phone'] ) ); ?>">
					<?php echo esc_html( $profile['phone'] ); ?>
				</a>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $profile['office'] ) ) : ?>
			<p class="ed-card__office"><?php echo esc_html( $profile['office'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $profile['bio'] ) ) : ?>
			<p class="ed-card__bio"><?php echo esc_html( $profile['bio'] ); ?></p>
		<?php endif; ?>
	</div>

</article>
