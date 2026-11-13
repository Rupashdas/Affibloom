<?php
/**
 * Lasso's Filters button and panel (dashboard.php, lasso-dashboard-filters-panel), with Lasso's fields, options,
 * labels and help text. Link type works (Task 13E); Group, Placements and Comparison table are there and say
 * "Coming soon" until the Groups area, the content link index and the Tables area bring their data.
 *
 * Lasso opens the panel beside the table; here it is a card under the button, because eight columns in a 1320px
 * column leave no room for a 280px side panel without squeezing the Link Name.
 *
 * @package Affibloom
 * @var array{link_kind: \Affibloom\Domain\Product\LinkKind} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Domain\Product\LinkKind;

$link_types = array(
	LinkKind::All->value       => __( 'All types', 'affibloom' ),
	LinkKind::Amazon->value    => __( 'Amazon', 'affibloom' ),
	LinkKind::NonAmazon->value => __( 'Non-Amazon', 'affibloom' ),
);
$pending    = array(
	// [ id, label, Lasso's first option ].
	array( 'affibloom-filter-group', __( 'Group', 'affibloom' ), __( 'All groups', 'affibloom' ) ),
	array( 'affibloom-filter-placements', __( 'Placements', 'affibloom' ), __( 'Any placement', 'affibloom' ) ),
	array( 'affibloom-filter-table', __( 'Comparison table', 'affibloom' ), __( 'Any', 'affibloom' ) ),
);
$active     = LinkKind::All === $view['link_kind'] ? 0 : 1;
?>
<div class="affibloom-filters">
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-filters__toggle"
		aria-expanded="false" aria-controls="affibloom-filters-panel">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'filter' ) ); ?>
		<span><?php esc_html_e( 'Filters', 'affibloom' ); ?></span>
		<span class="affibloom-filters__count" <?php echo 0 === $active ? 'hidden' : ''; ?>
			aria-label="<?php esc_attr_e( 'Active filters', 'affibloom' ); ?>"><?php echo esc_html( (string) $active ); ?></span>
	</button>

	<section id="affibloom-filters-panel" class="affibloom-filters__panel" hidden aria-labelledby="affibloom-filters-title">
		<div class="affibloom-filters__head">
			<h2 id="affibloom-filters-title" class="affibloom-filters__title"><?php esc_html_e( 'Filters', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-filters__close" aria-label="<?php esc_attr_e( 'Close filters', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>

		<div class="affibloom-filters__body">
			<?php foreach ( array_slice( $pending, 0, 1 ) as $field ) : ?>
				<div class="affibloom-filters__field">
					<label class="affibloom-filters__label" for="<?php echo esc_attr( $field[0] ); ?>"><?php echo esc_html( $field[1] ); ?>
						<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?></label>
					<select id="<?php echo esc_attr( $field[0] ); ?>" class="affibloom-select" disabled aria-describedby="affibloom-soon-note">
						<option><?php echo esc_html( $field[2] ); ?></option>
					</select>
				</div>
			<?php endforeach; ?>

			<div class="affibloom-filters__field">
				<label class="affibloom-filters__label" for="affibloom-filter-link-type"><?php esc_html_e( 'Link type', 'affibloom' ); ?></label>
				<select id="affibloom-filter-link-type" class="affibloom-select" name="link_type">
					<?php foreach ( $link_types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $value, $view['link_kind']->value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php foreach ( array_slice( $pending, 1 ) as $field ) : ?>
				<div class="affibloom-filters__field">
					<label class="affibloom-filters__label" for="<?php echo esc_attr( $field[0] ); ?>"><?php echo esc_html( $field[1] ); ?>
						<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?></label>
					<select id="<?php echo esc_attr( $field[0] ); ?>" class="affibloom-select" disabled aria-describedby="affibloom-soon-note">
						<option><?php echo esc_html( $field[2] ); ?></option>
					</select>
				</div>
			<?php endforeach; ?>

			<form class="affibloom-filters__field affibloom-filters__save" novalidate>
				<span class="affibloom-filters__label">
					<label for="affibloom-view-name"><?php esc_html_e( 'Save as', 'affibloom' ); ?></label>
					<?php
					$this->render(
						'admin/partials/help',
						array(
							'id'    => 'affibloom-view-name-help',
							'label' => __( 'Save as', 'affibloom' ),
							// Lasso's tip names the sort and the alert tab as well; they join a view with their tasks.
							'text'  => __( 'Saves your search, filters and page size under this name. Click the saved view pill to restore it.', 'affibloom' ),
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
				title="<?php esc_attr_e( 'Reset search, filters and page size', 'affibloom' ); ?>"><?php esc_html_e( 'Reset filters', 'affibloom' ); ?></button>
			<span class="affibloom-filters__hint"><?php esc_html_e( 'Filters apply as you toggle', 'affibloom' ); ?></span>
		</div>
	</section>
</div>
