<?php
/**
 * Employee directory front-end template.
 *
 * Variables provided by employee_dir_shortcode():
 *   @var WP_User[] $employees
 *   @var string[]  $departments
 *   @var string    $search
 *   @var string    $department
 *   @var int       $paged
 *   @var int       $total_pages
 *   @var string    $pagination         Pre-rendered pagination nav HTML.
 *   @var string    $locked_department  Non-empty when the shortcode locked a dept.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="internal-staff-directory" id="internal-staff-directory">

	<form class="ed-filters" id="ed-filter-form" method="get" role="search" aria-label="<?php esc_attr_e( 'Search employees', 'internal-staff-directory' ); ?>">
		<div class="ed-filter-row">
			<label for="ed-search" class="screen-reader-text">
				<?php esc_html_e( 'Search employees', 'internal-staff-directory' ); ?>
			</label>
			<input
				type="search"
				id="ed-search"
				name="ed_search"
				placeholder="<?php esc_attr_e( 'Search by name or email…', 'internal-staff-directory' ); ?>"
				value="<?php echo esc_attr( $search ); ?>"
				autocomplete="off"
			/>

			<?php if ( $departments && empty( $locked_department ) ) : ?>
				<label for="ed-department" class="screen-reader-text">
					<?php esc_html_e( 'Filter by department', 'internal-staff-directory' ); ?>
				</label>
				<select id="ed-department" name="ed_dept">
					<option value=""><?php esc_html_e( 'All departments', 'internal-staff-directory' ); ?></option>
					<?php foreach ( $departments as $dept ) : ?>
						<option value="<?php echo esc_attr( $dept ); ?>" <?php selected( $department, $dept ); ?>>
							<?php echo esc_html( $dept ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<label for="ed-sort" class="screen-reader-text">
				<?php esc_html_e( 'Sort by', 'internal-staff-directory' ); ?>
			</label>
			<select id="ed-sort" name="ed_sort">
				<option value="name_asc"><?php esc_html_e( 'A → Z', 'internal-staff-directory' ); ?></option>
				<option value="name_desc"><?php esc_html_e( 'Z → A', 'internal-staff-directory' ); ?></option>
				<option value="start_date_desc"><?php esc_html_e( 'Newest join date', 'internal-staff-directory' ); ?></option>
				<option value="department_asc"><?php esc_html_e( 'Department', 'internal-staff-directory' ); ?></option>
			</select>

			<button
				type="button"
				id="ed-view-toggle"
				class="ed-view-toggle"
				aria-pressed="false"
				aria-label="<?php esc_attr_e( 'Switch to list view', 'internal-staff-directory' ); ?>"
			><?php esc_html_e( 'List view', 'internal-staff-directory' ); ?></button>

			<button
				type="button"
				id="ed-vertical-toggle"
				class="ed-view-toggle"
				aria-pressed="false"
				aria-label="<?php esc_attr_e( 'Switch to vertical view', 'internal-staff-directory' ); ?>"
			><?php esc_html_e( 'Vertical view', 'internal-staff-directory' ); ?></button>
		</div>
	</form>

	<nav class="ed-az-nav" id="ed-az-nav" aria-label="<?php esc_attr_e( 'Jump to letter', 'internal-staff-directory' ); ?>">
		<?php foreach ( range( 'A', 'Z' ) as $az_letter ) : ?>
			<button type="button" class="ed-az-nav__btn" data-letter="<?php echo esc_attr( $az_letter ); ?>">
				<?php echo esc_html( $az_letter ); ?>
			</button>
		<?php endforeach; ?>
		<button type="button" class="ed-az-nav__btn ed-az-nav__all" data-letter="">
			<?php esc_html_e( 'All', 'internal-staff-directory' ); ?>
		</button>
	</nav>

	<div class="ed-results" id="ed-results" aria-live="polite" aria-atomic="true">
		<?php if ( $employees ) : ?>
			<?php
			$visible_fields = employee_dir_get_settings()['visible_fields'];
			foreach ( $employees as $user ) :
				$profile = employee_dir_get_profile( $user->ID );
				include __DIR__ . '/profile-card.php';
			endforeach; ?>
		<?php else : ?>
			<p class="ed-no-results"><?php esc_html_e( 'No employees found.', 'internal-staff-directory' ); ?></p>
		<?php endif; ?>
	</div>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pagination HTML is generated internally.
	echo $pagination;
	?>

</div>
