<?php
/**
 * Lasso's Filters button and panel (dashboard.php, lasso-dashboard-filters-panel), with Lasso's fields, options,
 * labels and help text: Link type (Task 13E), Group (Task G5), Placements (Task 36D) and Comparison table (F25 R59,
 * Task T16).
 *
 * Lasso opens the panel beside the table; here it is a card under the button, because eight columns in a 1320px
 * column leave no room for a 280px side panel without squeezing the Link Name.
 *
 * @package Affibloom
 * @var array{link_kind: \Affibloom\Domain\Product\LinkKind, group: int, group_list: list<\Affibloom\Domain\Group\Group>, args: array<string, string>, placement?: \Affibloom\Domain\Product\Placement, tables?: \Affibloom\Domain\Product\TableFilter} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\Placement;
use Affibloom\Domain\Product\TableFilter;

$link_types = array(
	LinkKind::All->value       => __( 'All types', 'affibloom' ),
	LinkKind::Amazon->value    => __( 'Amazon', 'affibloom' ),
	LinkKind::NonAmazon->value => __( 'Non-Amazon', 'affibloom' ),
);
$placements = array(
	Placement::Any->value          => __( 'Any placement', 'affibloom' ),
	Placement::InContent->value    => __( 'In content', 'affibloom' ),
	Placement::NotInContent->value => __( 'Not in content', 'affibloom' ),
);
$placement  = $view['placement'] ?? Placement::Any;
$in_tables  = $view['tables'] ?? TableFilter::Any;
$table_list = array(
	TableFilter::Any->value        => __( 'Any', 'affibloom' ),
	TableFilter::InTable->value    => __( 'In a comparison table', 'affibloom' ),
	TableFilter::NotInTable->value => __( 'Not in any table', 'affibloom' ),
);
$active     = ( LinkKind::All === $view['link_kind'] ? 0 : 1 ) + ( 0 === $view['group'] ? 0 : 1 ) + ( Placement::Any === $placement ? 0 : 1 ) + ( TableFilter::Any === $in_tables ? 0 : 1 );
?>
<div class="affibloom-filters">
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-filters__toggle"
		aria-expanded="false" aria-controls="affibloom-filters-panel">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'filter' ) ); ?>
		<span><?php esc_html_e( 'Filters', 'affibloom' ); ?></span>
		<span class="affibloom-filters__count" <?php echo 0 === $active ? 'hidden' : ''; ?>
			aria-label="<?php esc_attr_e( 'Active filters', 'affibloom' ); ?>"><?php echo esc_html( (string) $active ); ?></span>
	</button>

	<?php // Lasso's saved-view messages (view-js.php 1383-1476; Task 13J, F19 D14), shown by dashboard.js. ?>
	<section id="affibloom-filters-panel" class="affibloom-filters__panel" hidden aria-labelledby="affibloom-filters-title"
		data-saved-text="<?php esc_attr_e( 'View saved.', 'affibloom' ); ?>"
		data-deleted-text="<?php esc_attr_e( 'View deleted.', 'affibloom' ); ?>"
		data-name-first-text="<?php esc_attr_e( 'Name your view first.', 'affibloom' ); ?>"
		data-save-failed-text="<?php esc_attr_e( 'Could not save.', 'affibloom' ); ?>"
		data-delete-failed-text="<?php esc_attr_e( 'Could not delete.', 'affibloom' ); ?>">
		<div class="affibloom-filters__head">
			<h2 id="affibloom-filters-title" class="affibloom-filters__title"><?php esc_html_e( 'Filters', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-filters__close" aria-label="<?php esc_attr_e( 'Close filters', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>

		<div class="affibloom-filters__body">
			<?php
			// The working filters are one GET form (Task 13F; F19 D17): with JavaScript they load as you toggle, as
			// Lasso's do; without it, Apply submits them with the list's other arguments.
			?>
			<form class="affibloom-filters__form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<?php
			$this->render(
				'admin/partials/list-args',
				array(
					'args'   => $view['args'],
					'except' => array( 'link_type', 'group', 'placements', 'comparison_table' ),
				)
			);
			?>
			<?php // Lasso's Group filter (dashboard.php 158-165, Task G5): all groups, "Not in Groups", then every group. ?>
			<div class="affibloom-filters__field">
				<label class="affibloom-filters__label" for="affibloom-filter-group"><?php esc_html_e( 'Group', 'affibloom' ); ?></label>
				<select id="affibloom-filter-group" class="affibloom-select" name="group">
					<option value="0" <?php selected( 0, $view['group'] ); ?>><?php esc_html_e( 'All groups', 'affibloom' ); ?></option>
					<option value="-1" <?php selected( -1, $view['group'] ); ?>><?php esc_html_e( 'Not in Groups', 'affibloom' ); ?></option>
					<?php foreach ( $view['group_list'] as $choice ) : ?>
						<option value="<?php echo esc_attr( (string) $choice->id ); ?>" <?php selected( $choice->id, $view['group'] ); ?>><?php echo esc_html( wp_specialchars_decode( $choice->name, ENT_QUOTES ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="affibloom-filters__field">
				<label class="affibloom-filters__label" for="affibloom-filter-link-type"><?php esc_html_e( 'Link type', 'affibloom' ); ?></label>
				<select id="affibloom-filter-link-type" class="affibloom-select" name="link_type">
					<?php foreach ( $link_types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $value, $view['link_kind']->value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php // Lasso's Placements (dashboard.php 174-181; F12 L13, Task 36D): used somewhere by the Locations count's rule, or not. ?>
			<div class="affibloom-filters__field">
				<label class="affibloom-filters__label" for="affibloom-filter-placements"><?php esc_html_e( 'Placements', 'affibloom' ); ?></label>
				<select id="affibloom-filter-placements" class="affibloom-select" name="placements">
					<?php foreach ( $placements as $value => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $value, $placement->value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php // Lasso's Comparison table (dashboard.php 182-189; F25 R59, Task T16): in a table, or in none. ?>
			<div class="affibloom-filters__field">
				<label class="affibloom-filters__label" for="affibloom-filter-table"><?php esc_html_e( 'Comparison table', 'affibloom' ); ?></label>
				<select id="affibloom-filter-table" class="affibloom-select" name="comparison_table">
					<?php foreach ( $table_list as $value => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $value, $in_tables->value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="submit" class="affibloom-button affibloom-button--small affibloom-button--primary affibloom-no-js"><?php esc_html_e( 'Apply', 'affibloom' ); ?></button>
			</form>

			<form class="affibloom-filters__field affibloom-filters__save" novalidate>
				<span class="affibloom-filters__label">
					<label for="affibloom-view-name"><?php esc_html_e( 'Save as', 'affibloom' ); ?></label>
					<?php
					$this->render(
						'admin/partials/help',
						array(
							'id'    => 'affibloom-view-name-help',
							'label' => __( 'Save as', 'affibloom' ),
							'text'  => __( 'Saves your search, filters, sort, page size, and alert tab under this name. Click the saved view pill to restore it.', 'affibloom' ),
						)
					);
					?>
				</span>
				<span class="affibloom-filters__inline">
					<input type="text" id="affibloom-view-name" class="affibloom-input" maxlength="<?php echo esc_attr( (string) SavedViews::NAME_LIMIT ); ?>"
						placeholder="<?php esc_attr_e( 'Name this view', 'affibloom' ); ?>" aria-describedby="affibloom-view-name-help affibloom-view-error">
					<button type="submit" class="affibloom-button affibloom-button--small affibloom-button--primary"><?php esc_html_e( 'Save', 'affibloom' ); ?></button>
				</span>
				<span id="affibloom-view-error" class="affibloom-field__error" role="alert"></span>
			</form>
		</div>

		<div class="affibloom-filters__foot">
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-filters__reset"
				title="<?php esc_attr_e( 'Reset search, filters, sort, page size, and alert tab', 'affibloom' ); ?>"><?php esc_html_e( 'Reset filters', 'affibloom' ); ?></button>
			<span class="affibloom-filters__hint"><?php esc_html_e( 'Filters apply as you toggle', 'affibloom' ); ?></span>
		</div>
	</section>
</div>
