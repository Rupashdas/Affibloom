<?php
/**
 * A field's page, or "Add a New Field" (Lasso's `admin/views/fields/{header,field-details,field-urls}.php`; F26 R12-R13,
 * R20): the page head holds the heading and, for an existing field, its tabs, Links (with the number of products it
 * lists) and Details. The Links tab lists the published products that have the field; the Details form saves through
 * POST /fields (a new field) or PATCH /fields/{id} (assets/admin/fields.js); Delete This Field asks, or says "Hold Up"
 * when a product has the field.
 *
 * @package Affibloom
 * @var array{field: ?\Affibloom\Domain\Fields\Field, tab: string, products: ?\Affibloom\Domain\Product\ProductPage, types: list<\Affibloom\Domain\Fields\FieldType>, usage: int, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\FieldScreen;

$field       = $view['field'];
$is_new      = null === $field;
$heading     = $is_new ? __( 'Add a New Field', 'affibloom' ) : $field->name;
$current_tab = $view['tab'];
$products    = $view['products'];
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		// An existing field has its tabs: Links, with the number of published products it lists (F26 D7; none, no
		// number, R12), and Details. "Add a New Field" has the title only.
		$field_head = array(
			'title'    => $heading,
			'title_id' => 'affibloom-field-title',
		);
		if ( ! $is_new ) {
			$field_head['tabs'] = array(
				'label' => __( 'Field sections', 'affibloom' ),
				'items' => array(
					array(
						'label'   => __( 'Links', 'affibloom' ),
						'url'     => FieldScreen::url( $field->id, 'links' ),
						'current' => 'links' === $current_tab,
						'count'   => null !== $products ? $products->total : 0,
					),
					array(
						'label'   => __( 'Details', 'affibloom' ),
						'url'     => FieldScreen::url( $field->id, 'details' ),
						'current' => 'details' === $current_tab,
					),
				),
			);
		}
		$this->render( 'admin/partials/page-head', $field_head );
		?>

		<div class="affibloom-page__body affibloom-fieldpage">
			<?php if ( ! $is_new && 'links' === $current_tab && null !== $products ) : ?>
				<?php
				$this->render(
					'admin/partials/field-links',
					array(
						'field'    => $field,
						'products' => $products,
					)
				);
				?>
			<?php else : ?>
				<?php
				$this->render(
					'admin/partials/field-form',
					array(
						'field' => $field,
						'types' => $view['types'],
						'usage' => $view['usage'],
					)
				);
				?>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( ! $is_new && ! $field->built_in && 'details' === $current_tab ) : ?>
		<?php
		// Lasso's two answers to Delete This Field (modals/field-delete.php): the question, and "Hold Up" when links use it.
		$this->render(
			'admin/partials/delete-modal',
			array(
				'id'           => 'affibloom-field-delete',
				'title'        => __( 'Delete This Field?', 'affibloom' ),
				'text'         => __( "If deleted, you won't be able to get it back.", 'affibloom' ),
				'confirm_attr' => 'data-field-delete-confirm',
				'failed_text'  => __( 'Could not delete the field. Please try again.', 'affibloom' ),
			)
		);
		$this->render(
			'admin/partials/holdup-modal',
			array(
				'id'   => 'affibloom-field-holdup',
				'text' => __( "You can't delete a Field if there are Links using it. Remove all Links using this Field first.", 'affibloom' ),
			)
		);
		?>
	<?php endif; ?>
	<?php $this->render( 'admin/partials/save-modal' ); ?>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
