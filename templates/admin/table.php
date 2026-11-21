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
use Affibloom\Interface\ShortcodeText;

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
		data-saving="<?php esc_attr_e( 'Saving…', 'affibloom' ); ?>" data-saved="<?php esc_attr_e( 'Saved', 'affibloom' ); ?>" data-unsaved="<?php esc_attr_e( 'Not saved', 'affibloom' ); ?>"
		data-failed="<?php esc_attr_e( 'Could not save. Please try again.', 'affibloom' ); ?>" data-reload="<?php esc_attr_e( 'Reload', 'affibloom' ); ?>"
		data-table-style="<?php echo esc_attr( $table->style->value ); ?>"
		<?php /* translators: 1: what is moved, 2: its place, 3: how many places. */ ?>
		data-drag-lifted="<?php esc_attr_e( 'Picked up %1$s, place %2$s of %3$s. Move with the arrow keys, drop with Space.', 'affibloom' ); ?>"
		<?php /* translators: 1: what is moved, 2: its place, 3: how many places. */ ?>
		data-drag-moved="<?php esc_attr_e( '%1$s, place %2$s of %3$s.', 'affibloom' ); ?>"
		<?php /* translators: 1: what was moved, 2: its place, 3: how many places. */ ?>
		data-drag-dropped="<?php esc_attr_e( 'Dropped %1$s at place %2$s of %3$s.', 'affibloom' ); ?>"
		<?php /* translators: %1$s: what was being moved. */ ?>
		data-drag-cancelled="<?php esc_attr_e( 'Put %1$s back.', 'affibloom' ); ?>"
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
					'saved'     => null !== $table->id,
					'has_id'    => null !== $table->id,
					'shortcode' => ShortcodeText::table( (int) $table->id ),
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
			<?php if ( null === $table->id ) : ?>
				<p class="affibloom-field__hint" id="affibloom-table-name-first"><?php esc_html_e( 'Name the table to create it; Theme and the options follow once it exists.', 'affibloom' ); ?></p>
			<?php endif; ?>
			<p class="affibloom-field__error affibloom-table-page__error" data-table-error role="alert" hidden></p>

			<?php if ( null !== $table->id ) : ?>
				<?php
				// The toolbar stays outside the redrawn canvas (deviation 151): its labels and the preview's state survive a redraw.
				?>
				<div class="affibloom-table-toolbar" data-table-toolbar role="toolbar" aria-label="<?php esc_attr_e( 'Table tools', 'affibloom' ); ?>">
					<button type="button" class="affibloom-button affibloom-button--secondary" data-table-action="add-product" aria-haspopup="dialog">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Add Product', 'affibloom' ); ?></span>
					</button>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-table-action="add-group" aria-haspopup="dialog">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'layers' ) ); ?>
						<span><?php esc_html_e( 'Add Group', 'affibloom' ); ?></span>
					</button>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-table-action="add-line">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span data-line-label data-row="<?php esc_attr_e( 'Add Row', 'affibloom' ); ?>" data-column="<?php esc_attr_e( 'Add Column', 'affibloom' ); ?>"><?php echo esc_html( $horizontal ? __( 'Add Column', 'affibloom' ) : __( 'Add Row', 'affibloom' ) ); ?></span>
					</button>
					<?php // R54 (Task T15): Preview Table, which reads Edit Table while the preview shows. ?>
					<button type="button" class="affibloom-button affibloom-button--secondary affibloom-table-toolbar__preview" data-table-action="preview" aria-pressed="false"
						data-preview-label="<?php esc_attr_e( 'Preview Table', 'affibloom' ); ?>" data-edit-label="<?php esc_attr_e( 'Edit Table', 'affibloom' ); ?>">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'eye' ) ); ?>
						<span data-preview-text><?php esc_html_e( 'Preview Table', 'affibloom' ); ?></span>
					</button>
				</div>
			<?php endif; ?>

			<div class="affibloom-table-workspace<?php echo null === $table->id ? ' affibloom-table-workspace--new' : ''; ?>">
				<div class="affibloom-table-workspace__canvas">
					<div id="affibloom-table-builder" data-table-builder-region>
						<?php $this->render( 'admin/partials/table-builder', $view['builder'] ); ?>
					</div>
					<?php // R54 (Task T15): the reader's table, in its own frame, while Preview Table is on. ?>
					<div class="affibloom-card affibloom-table-preview" data-table-preview hidden data-frame-title="<?php esc_attr_e( 'Table preview', 'affibloom' ); ?>"></div>
				</div>
				<?php if ( null !== $table->id ) : ?>
					<?php
					$this->render(
						'admin/partials/table-inspector',
						array(
							'icons'   => $view['icons'] ?? array(),
							'can_add' => $view['can_add_icon'] ?? false,
						)
					);
					?>
				<?php endif; ?>
			</div>
			<?php // Deviation 151: the keyboard's way to drag, and what a drag says to a screen reader. ?>
			<p id="affibloom-table-drag-help" class="screen-reader-text"><?php esc_html_e( 'Press Space or Enter to pick it up, the arrow keys to move it, Space or Enter to drop it, Escape to put it back.', 'affibloom' ); ?></p>
			<div class="screen-reader-text" aria-live="assertive" data-table-announce></div>

			<p class="affibloom-table-page__delete">
				<?php // R41, D12 (Task T11): unused, Lasso's question; used, "Hold Up". The server checks again. ?>
				<button type="button" class="affibloom-button affibloom-button--danger" data-table-delete aria-haspopup="dialog"<?php echo null === $table->id ? ' hidden' : ''; ?>><?php esc_html_e( 'Delete This Table', 'affibloom' ); ?></button>
			</p>
		</div>
	</div>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/table-create-modal' ); ?>
	<?php $this->render( 'admin/partials/table-product-picker' ); ?>
	<?php $this->render( 'admin/partials/table-group-picker' ); ?>
	<?php $this->render( 'admin/partials/table-description-modal' ); ?>
	<?php
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-table-delete',
			/* translators: %s: the table's name. */
			'title'         => sprintf( __( 'Delete "%s"', 'affibloom' ), $table->name ),
			'text'          => __( "If deleted, you won't be able to get its back.", 'affibloom' ),
			'confirm_label' => __( 'OK', 'affibloom' ),
			'confirm_attr'  => 'data-table-delete-confirm',
			'failed_text'   => __( 'Could not delete the table. Please try again.', 'affibloom' ),
		)
	);
	$this->render(
		'admin/partials/holdup-modal',
		array(
			'id'     => 'affibloom-table-holdup',
			'text'   => __( "You can't delete a Table if it is in use on your site. Remove this Table from your content first.", 'affibloom' ),
			'button' => __( 'Got It', 'affibloom' ),
		)
	);
	?>
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
	// A whole row (R33) or column: what goes with it, what is kept (D7).
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-table-remove-row',
			'title'         => $horizontal ? __( 'Remove this column?', 'affibloom' ) : __( 'Remove this row?', 'affibloom' ),
			'text'          => __( 'Its fields leave every product. Their values are kept, so adding a field back brings them back.', 'affibloom' ),
			'confirm_label' => __( 'Remove', 'affibloom' ),
			'confirm_attr'  => 'data-table-remove-row-confirm',
			'busy_label'    => __( 'Removing…', 'affibloom' ),
			'failed_text'   => __( 'Could not remove it. Please try again.', 'affibloom' ),
			'data'          => array(
				'row-title'    => __( 'Remove this row?', 'affibloom' ),
				'column-title' => __( 'Remove this column?', 'affibloom' ),
			),
		)
	);
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
