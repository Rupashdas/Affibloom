<?php
/**
 * A field's page, or "Add a New Field" (Lasso's `admin/views/fields/{header,field-details}.php`; F26 R12-R13): the band
 * holds the heading and, for an existing field, its tabs; the card holds the form. Task FL2 builds the new-field form,
 * which saves through POST /fields (assets/admin/fields.js); FL3 adds a field's Details tab and FL4 its Links tab.
 *
 * @package Affibloom
 * @var array{types: list<\Affibloom\Domain\Fields\FieldType>, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'fields' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title" id="affibloom-field-title"><?php esc_html_e( 'Add a New Field', 'affibloom' ); ?></h1>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-fieldpage">
			<?php $this->render( 'admin/partials/field-form', array( 'types' => $view['types'] ) ); ?>
		</div>
	</div>
	<?php $this->render( 'admin/partials/save-modal' ); ?>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
</div>
