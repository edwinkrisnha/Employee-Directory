<?php
/**
 * Employee directory front-end template.
 *
 * Variables provided by employee_dir_shortcode():
 *   @var WP_User[] $employees
 *   @var string[]  $departments
 *   @var string    $search
 *   @var string    $department
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="employee-directory" id="employee-directory">

	<form class="ed-filters" id="ed-filter-form" method="get" role="search" aria-label="<?php esc_attr_e( 'Search employees', 'employee-directory' ); ?>">
		<div class="ed-filter-row">
			<label for="ed-search" class="screen-reader-text">
				<?php esc_html_e( 'Search employees', 'employee-directory' ); ?>
			</label>
			<input
				type="search"
				id="ed-search"
				name="ed_search"
				placeholder="<?php esc_attr_e( 'Search by name or email\xe2\x80\xa6', 'employee-directory' ); ?>"
				value="<?php echo esc_attr( $search ); ?>"
				autocomplete="off"
			/>

			<?php if ( $departments ) : ?>
				<label for="ed-department" class="screen-reader-text">
					<?php esc_html_e( 'Filter by department', 'employee-directory' ); ?>
				</label>
				<select id="ed-department" name="ed_dept">
					<option value=""><?php esc_html_e( 'All departments', 'employee-directory' ); ?></option>
					<?php foreach ( $departments as $dept ) : ?>
						<option value="<?php echo esc_attr( $dept ); ?>" <?php selected( $department, $dept ); ?>>
							<?php echo esc_html( $dept ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</div>
	</form>

	<div class="ed-results" id="ed-results" aria-live="polite" aria-atomic="true">
		<?php if ( $employees ) : ?>
			<?php foreach ( $employees as $user ) :
				$profile = employee_dir_get_profile( $user->ID );
				include __DIR__ . '/profile-card.php';
			endforeach; ?>
		<?php else : ?>
			<p class="ed-no-results"><?php esc_html_e( 'No employees found.', 'employee-directory' ); ?></p>
		<?php endif; ?>
	</div>

</div>
