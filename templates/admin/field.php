<?php
/**
 * A field's page, or "Add a New Field" (Lasso's `admin/views/fields/{header,field-details}.php`; F26 R12-R13): the band
 * holds the heading and, for an existing field, its tabs; the card holds the form. The new-field form saves through
 * POST /fields and a field's Details through PATCH /fields/{id} (assets/admin/fields.js); Delete This Field asks, or says
 * "Hold Up" when a product has the field. Task FL4 adds the Links tab.
 *
 * @package Affibloom
 * @var array{field: ?\Affibloom\Domain\Fields\Field, types: list<\Affibloom\Domain\Fields\FieldType>, usage: int, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\FieldScreen;

$field   = $view['field'];
$is_new  = null === $field;
$heading = $is_new ? __( 'Add a New Field', 'affibloom' ) : $field->name;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'fields' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title" id="affibloom-field-title"><?php echo esc_html( $heading ); ?></h1>
				<?php if ( ! $is_new ) : ?>
					<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Field sections', 'affibloom' ); ?>">
						<a class="affibloom-tabs__tab is-active" aria-current="page" href="<?php echo esc_url( FieldScreen::url( $field->id, 'details' ) ); ?>"><?php esc_html_e( 'Details', 'affibloom' ); ?></a>
					</nav>
				<?php endif; ?>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-fieldpage">
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
		</div>
	</div>
	<?php if ( ! $is_new && ! $field->built_in ) : ?>
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
