<?php
/**
 * A comparison table's builder (F25 R13-R16, D4, D5; Task T3; Lasso's tables/header.php and table-details.php): the
 * heading (the name, live while typing), Create Table, Copy Shortcode, Saved / Saving and All Tables <n>; the settings
 * row (Name, Options, Style, Theme); the builder, which every write redraws; Delete This Table. Until a table has an id
 * (no `id` in the address), naming it creates it.
 *
 * @package Affibloom
 * @var array{table: \Affibloom\Domain\Table\Table|null, untitled: string, all: int, builder: array<string, mixed>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableStyle;
use Affibloom\Domain\Table\TableTheme;
use Affibloom\Interface\Admin\RichEditorSettings;
use Affibloom\Interface\Admin\TablesScreen;

$table      = $view['table'] ?? new Table( null, '' );
$horizontal = TableStyle::Row === $table->style;
$heading    = '' !== $table->name ? $table->name : $view['untitled'];
/* translators: %s: how many tables there are. */
$all_label = sprintf( __( 'All Tables %s', 'affibloom' ), number_format_i18n( $view['all'] ) );
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page affibloom-table-page" data-table-builder data-table-id="<?php echo esc_attr( (string) ( $table->id ?? 0 ) ); ?>"
		data-revision="<?php echo esc_attr( (string) $table->revision ); ?>" data-untitled="<?php echo esc_attr( $view['untitled'] ); ?>"
		data-saving="<?php esc_attr_e( 'Saving', 'affibloom' ); ?>" data-saved="<?php esc_attr_e( 'Saved', 'affibloom' ); ?>"
		data-failed="<?php esc_attr_e( 'Could not save. Please try again.', 'affibloom' ); ?>"
		data-media-title="<?php esc_attr_e( 'Select an Image', 'affibloom' ); ?>" data-media-button="<?php esc_attr_e( 'Select Image', 'affibloom' ); ?>"
		data-price-editor="<?php echo esc_attr( (string) wp_json_encode( RichEditorSettings::for( 'price', '', __( 'N/A', 'affibloom' ) ) ) ); ?>">
		<?php
		$this->render(
			'admin/partials/page-head',
			array(
				'title'        => $heading,
				'title_id'     => 'affibloom-table-heading',
				'back'         => array(
					'url'   => TablesScreen::url(),
					'label' => $all_label,
				),
				'actions'      => 'admin/partials/table-head-actions',
				'actions_args' => array(
					'saved'  => null !== $table->id,
					'has_id' => null !== $table->id,
				),
			)
		);
		?>

		<div class="affibloom-page__body">
			<form class="affibloom-card affibloom-table-settings" data-table-settings>
				<div class="affibloom-table-settings__field">
					<label class="affibloom-label" for="affibloom-table-name"><?php esc_html_e( 'Name', 'affibloom' ); ?></label>
					<input type="text" id="affibloom-table-name" class="affibloom-input" name="title" value="<?php echo esc_attr( $table->name ); ?>"
						placeholder="<?php echo esc_attr( $heading ); ?>" title="<?php esc_attr_e( 'Use this to identify your tables', 'affibloom' ); ?>" autocomplete="off">
				</div>
				<div class="affibloom-table-settings__field">
					<span class="affibloom-label"><?php esc_html_e( 'Options', 'affibloom' ); ?></span>
					<?php
					$this->render(
						'admin/partials/switch-row',
						array(
							'id'      => 'affibloom-table-show-field-name',
							'name'    => 'show_field_name',
							'label'   => __( 'Show Field Name', 'affibloom' ),
							'checked' => $table->show_field_name,
							'help'    => __( 'Show field name in a Table', 'affibloom' ),
						)
					);
					?>
					<div data-table-headers-row<?php echo $horizontal ? '' : ' hidden'; ?>>
						<?php
						$this->render(
							'admin/partials/switch-row',
							array(
								'id'      => 'affibloom-table-show-headers',
								'name'    => 'show_headers',
								'label'   => __( 'Show Headers', 'affibloom' ),
								'checked' => $table->show_headers,
								'help'    => __( 'Hide the column header in a Horizontal Table', 'affibloom' ),
							)
						);
						?>
					</div>
				</div>
				<div class="affibloom-table-settings__field">
					<label class="affibloom-label" for="affibloom-table-style"><?php esc_html_e( 'Style', 'affibloom' ); ?></label>
					<select id="affibloom-table-style" class="affibloom-select" name="style" title="<?php esc_attr_e( 'This changes the orientation of your table', 'affibloom' ); ?>">
						<option value="<?php echo esc_attr( TableStyle::Row->value ); ?>" <?php selected( $horizontal ); ?>><?php esc_html_e( 'Horizontal', 'affibloom' ); ?></option>
						<option value="<?php echo esc_attr( TableStyle::Column->value ); ?>" <?php selected( ! $horizontal ); ?>><?php esc_html_e( 'Vertical', 'affibloom' ); ?></option>
					</select>
				</div>
				<div class="affibloom-table-settings__field">
					<label class="affibloom-label" for="affibloom-table-theme"><?php esc_html_e( 'Theme', 'affibloom' ); ?></label>
					<select id="affibloom-table-theme" class="affibloom-select" name="theme" title="<?php esc_attr_e( 'Change the look and feel of your table', 'affibloom' ); ?>">
						<?php foreach ( TableTheme::ALL as $theme ) : ?>
							<option value="<?php echo esc_attr( $theme ); ?>" <?php selected( $theme, $table->theme ); ?>><?php echo esc_html( $theme ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</form>
			<p class="affibloom-field__error" data-table-error role="alert" hidden></p>

			<div id="affibloom-table-builder" data-table-builder-region>
				<?php $this->render( 'admin/partials/table-builder', $view['builder'] ); ?>
			</div>

			<p class="affibloom-table-page__delete">
				<?php // Delete This Table comes with Task T11. ?>
				<button type="button" class="affibloom-button affibloom-button--danger" disabled title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>"><?php esc_html_e( 'Delete This Table', 'affibloom' ); ?></button>
			</p>
		</div>
	</div>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/table-create-modal' ); ?>
	<?php $this->render( 'admin/partials/table-product-picker' ); ?>
	<?php $this->render( 'admin/partials/table-group-picker' ); ?>
	<?php $this->render( 'admin/partials/table-description-modal' ); ?>
	<?php $this->render( 'admin/partials/table-button-modal' ); ?>
	<?php
	$this->render(
		'admin/partials/field-modal',
		array(
			'types'  => FieldType::choosable(),
			'remove' => false,
		)
	);
	?>
	<?php
	// Lasso's 'Remove "<field>"?' (R31); the script puts the field's name in the title.
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-table-remove-field',
			'title'         => '',
			'text'          => __( 'No data is deleted and the Field can always be added back.', 'affibloom' ),
			'confirm_label' => __( 'Remove', 'affibloom' ),
			'confirm_attr'  => 'data-table-remove-field-confirm',
			'busy_label'    => __( 'Removing…', 'affibloom' ),
			'failed_text'   => __( 'Could not remove the field. Please try again.', 'affibloom' ),
			/* translators: %s: the field's name. */
			'data'          => array( 'title-template' => __( 'Remove "%s"?', 'affibloom' ) ),
		)
	);
	?>
	<?php
	// Lasso's 'Remove "<name>"?' (R23); the script puts the product's name in the title.
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-table-remove-product',
			'title'         => '',
			'text'          => __( "If removed, you won't be able to get its back.", 'affibloom' ),
			'confirm_label' => __( 'OK', 'affibloom' ),
			'confirm_attr'  => 'data-table-remove-confirm',
			'busy_label'    => __( 'Removing…', 'affibloom' ),
			'failed_text'   => __( 'Could not remove the product. Please try again.', 'affibloom' ),
			/* translators: %s: the product's name. */
			'data'          => array( 'title-template' => __( 'Remove "%s"?', 'affibloom' ) ),
		)
	);
	?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
