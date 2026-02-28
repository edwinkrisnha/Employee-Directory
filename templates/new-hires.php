<?php
/**
 * New hires spotlight template.
 *
 * Variables provided by employee_dir_new_hires_shortcode():
 *   @var WP_User[] $employees      Users within the new_hire_days window.
 *   @var string[]  $visible_fields Fields enabled in plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="internal-staff-directory ed-new-hires" id="ed-new-hires">

	<div class="ed-results" id="ed-results">
		<?php if ( $employees ) : ?>
			<?php foreach ( $employees as $user ) :
				$profile = employee_dir_get_profile( $user->ID );
				include __DIR__ . '/profile-card.php';
			endforeach; ?>
		<?php else : ?>
			<p class="ed-no-results"><?php esc_html_e( 'No new employees found.', 'internal-staff-directory' ); ?></p>
		<?php endif; ?>
	</div>

</div>
