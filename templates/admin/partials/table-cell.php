<?php
/**
 * One builder cell's field cards (F25 R29, R31, R34, R35; Tasks T4, T6-T9; Lasso's field-details-row.php; deviation
 * 151). A card is Lasso's: a grip on its edge (it drags by the grip or its body), the field's name, its editor and its
 * remove (×). Text, product name and copy-to-clipboard: a one-line box (the field's description as the placeholder);
 * long text and lists: a three-line box; number: a number box; rating: 1 to 5 in steps of 0.1 with its stars; the
 * image opens the media library; the price opens a one-line rich editor in place (an Amazon product's price is shown
 * read only); the description opens "Description Editor"; a button is drawn with its icon and text and is edited in
 * the inspector. table-builder-cells.js saves a value 500 ms after it changes.
 *
 * @package Affibloom
 * @var array{fields: array<int, \Affibloom\Domain\Fields\Field>, order: list<int>, values: array<int, string>, product: int, amazon?: string|null, icons?: array<string, string>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Icons\IconText;
use Affibloom\Domain\Table\StartingLayout;

foreach ( $view['order'] as $field_id ) :
	$field = $view['fields'][ $field_id ] ?? null;
	if ( null === $field ) {
		continue;
	}
	$value  = (string) ( $view['values'][ $field_id ] ?? '' );
	$button = FieldType::Button === $field->type ? json_decode( $value, true ) : null;
	$text   = is_array( $button ) ? (string) ( $button['button_text'] ?? '' ) : $value;
	$key    = $view['product'] . '-' . $field->id;
	$input  = 'affibloom-table-value-' . $key;
	$line   = in_array( $field->type, array( FieldType::Text, FieldType::ProductName, FieldType::CopyClipboard ), true );
	$lines  = in_array( $field->type, array( FieldType::Textarea, FieldType::BulletedList, FieldType::NumberedList ), true );
	?>
	<div class="affibloom-table-field affibloom-table-field--<?php echo esc_attr( $field->type->value ); ?>" data-table-field="<?php echo esc_attr( (string) $field->id ); ?>"
		data-field-name="<?php echo esc_attr( $field->name ); ?>" data-field-type="<?php echo esc_attr( $field->type->value ); ?>" data-field-kind="<?php echo esc_attr( $field->type->label() ); ?>">
		<button type="button" id="affibloom-grip-field-<?php echo esc_attr( $key ); ?>" class="affibloom-table-grip affibloom-table-field__grip" data-table-drag="field"
			<?php /* translators: %s: the field's name. */ ?>
			aria-label="<?php echo esc_attr( sprintf( __( 'Move %s', 'affibloom' ), $field->name ) ); ?>" aria-describedby="affibloom-table-drag-help">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'grip' ) ); ?>
		</button>
		<div class="affibloom-table-field__body">
			<div class="affibloom-table-field__head">
				<label class="affibloom-table-field__name" for="<?php echo esc_attr( $input ); ?>"><?php echo esc_html( $field->name ); ?></label>
				<?php /* translators: %s: the field's name. */ ?>
				<button type="button" class="affibloom-table-field__remove" data-table-remove-field aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'affibloom' ), $field->name ) ); ?>" title="<?php esc_attr_e( 'Remove from every product', 'affibloom' ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
				</button>
			</div>
			<?php if ( $line ) : ?>
				<input type="text" id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-table-field__input" data-table-value value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $field->description ); ?>" autocomplete="off">
			<?php elseif ( $lines ) : ?>
				<textarea id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-input--area affibloom-table-field__input" rows="3" data-table-value placeholder="<?php echo esc_attr( $field->description ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
			<?php elseif ( FieldType::Number === $field->type ) : ?>
				<input type="number" id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-table-field__input" data-table-value value="<?php echo esc_attr( $value ); ?>" step="any">
			<?php elseif ( FieldType::Rating === $field->type ) : ?>
				<div class="affibloom-table-field__rating">
					<input type="number" id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-table-field__input" data-table-value data-table-rating value="<?php echo esc_attr( $value ); ?>" min="1" max="5" step="0.1" placeholder="3.5">
					<span class="affibloom-stars" style="--rating: <?php echo esc_attr( is_numeric( $value ) ? $value : '0' ); ?>;" aria-hidden="true" data-table-stars></span>
				</div>
			<?php elseif ( FieldType::Image === $field->type ) : ?>
				<button type="button" id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__picture" data-table-image>
					<?php if ( '' !== $value ) : ?>
						<img class="affibloom-table-field__image" src="<?php echo esc_url( $value ); ?>" alt="" loading="lazy" draggable="false">
					<?php else : ?>
						<span class="affibloom-table-field__no-image"><?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?></span>
					<?php endif; ?>
					<span class="affibloom-table-field__update"><?php esc_html_e( 'Update Image', 'affibloom' ); ?></span>
				</button>
			<?php elseif ( FieldType::Label === $field->type && null !== ( $view['amazon'] ?? null ) ) : ?>
				<?php // R29: an Amazon product's price is Amazon's, kept up to date by the sync; it is not typed here. ?>
				<span id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__value"><?php echo '' !== $view['amazon'] ? wp_kses_post( $view['amazon'] ) : '—'; ?></span>
				<span class="affibloom-table-field__note"><?php esc_html_e( 'Amazon prices are updated automatically', 'affibloom' ); ?></span>
			<?php elseif ( FieldType::Label === $field->type ) : ?>
				<?php // Named "Edit <field>" and then its value, read from the cell itself, so an empty cell still has a name (Task 37C). ?>
				<span id="<?php echo esc_attr( $input . '-label' ); ?>" class="screen-reader-text">
					<?php /* translators: %s: the field's name. */ ?>
					<?php echo esc_html( sprintf( __( 'Edit %s', 'affibloom' ), $field->name ) ); ?>
				</span>
				<div id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__price" role="button" tabindex="0" data-table-price
					aria-labelledby="<?php echo esc_attr( $input . '-label ' . $input ); ?>"
					data-placeholder="<?php esc_attr_e( 'N/A', 'affibloom' ); ?>"><?php echo wp_kses_post( $value ); ?></div>
			<?php elseif ( FieldType::Editor === $field->type ) : ?>
				<div class="affibloom-table-field__description">
					<div class="affibloom-table-field__rich" data-table-rich><?php echo wp_kses_post( $value ); ?></div>
					<?php /* translators: %s: the field's name. */ ?>
					<button type="button" id="<?php echo esc_attr( $input ); ?>" class="affibloom-icon-button" data-table-description aria-haspopup="dialog" aria-label="<?php echo esc_attr( sprintf( __( 'Edit %s', 'affibloom' ), $field->name ) ); ?>" title="<?php esc_attr_e( 'Edit', 'affibloom' ); ?>">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'pencil' ) ); ?>
					</button>
				</div>
			<?php elseif ( FieldType::Button === $field->type ) : ?>
				<?php
				// R29, R35: the button with its icon and text; it opens the inspector's button editor. An empty text draws the site's
				// default button text on the page (R22), so the builder names the field. A Secondary without an address says so.
				$url     = is_array( $button ) ? (string) ( $button['url'] ?? '' ) : '';
				$primary = StartingLayout::PRIMARY_BUTTON === $field->id;
				?>
				<button type="button" id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-button-chip" data-table-button
					data-button-text="<?php echo esc_attr( $text ); ?>" data-button-url="<?php echo esc_attr( $url ); ?>" data-button-primary="<?php echo esc_attr( $primary ? '1' : '0' ); ?>"
					<?php /* translators: %s: the field's name. */ ?>
					aria-label="<?php echo esc_attr( sprintf( __( 'Edit %s', 'affibloom' ), $field->name ) ); ?>">
					<?php
					$this->render(
						'admin/partials/table-icon-text',
						array(
							'text'     => IconText::split( $text ),
							'raw'      => $text,
							'icons'    => $view['icons'] ?? array(),
							'fallback' => $field->name,
						)
					);
					?>
				</button>
				<?php if ( ! $primary && '' === $url ) : ?>
					<span class="affibloom-table-field__note"><?php esc_html_e( 'No Secondary Button URL set.', 'affibloom' ); ?></span>
				<?php endif; ?>
			<?php elseif ( '' === $value ) : ?>
				<span id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__empty">—</span>
			<?php else : ?>
				<span id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__value"><?php echo wp_kses_post( $value ); ?></span>
			<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
