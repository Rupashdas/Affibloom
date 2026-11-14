<?php
/**
 * A field's page, or "Add a New Field" (Lasso's `admin/views/fields/{header,field-details,field-urls}.php`; F26 R12-R13,
 * R20): the band holds the heading and, for an existing field, its tabs, Links (with the number of products it lists) and
 * Details. The Links tab lists the published products that have the field; the Details form saves through POST /fields (a
 * new field) or PATCH /fields/{id} (assets/admin/fields.js); Delete This Field asks, or says "Hold Up" when a product
 * has the field.
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
	<?php $this->render( 'admin/partials/header', array( 'current' => 'fields' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title" id="affibloom-field-title"><?php echo esc_html( $heading ); ?></h1>
				<?php if ( ! $is_new ) : ?>
					<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Field sections', 'affibloom' ); ?>">
						<a class="affibloom-tabs__tab<?php echo 'links' === $current_tab ? ' is-active' : ''; ?>" <?php echo 'links' === $current_tab ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( FieldScreen::url( $field->id, 'links' ) ); ?>">
							<?php esc_html_e( 'Links', 'affibloom' ); ?>
							<?php // The number is what the tab lists (published products), from the server (F26 D7); none, no number (R12). ?>
							<?php if ( null !== $products && $products->total > 0 ) : ?>
								<span class="affibloom-tabs__count"><?php echo esc_html( number_format_i18n( $products->total ) ); ?></span>
							<?php endif; ?>
						</a>
						<a class="affibloom-tabs__tab<?php echo 'details' === $current_tab ? ' is-active' : ''; ?>" <?php echo 'details' === $current_tab ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( FieldScreen::url( $field->id, 'details' ) ); ?>"><?php esc_html_e( 'Details', 'affibloom' ); ?></a>
					</nav>
				<?php endif; ?>
			</div>
		</div>

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
</div>
