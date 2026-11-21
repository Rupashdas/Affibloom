<?php
/**
 * One field of one product in a reader's comparison table (F25 R48-R50; Task T12; Lasso's fields/field-mapping.php
 * under Affibloom's names). A rating: its stars (an empty value is 3.5). Every other field inside `div.affibloom-fields`:
 * the product name (a link with the box's attributes when Link from Display Title is on, else an `h3`; the Prime logo
 * when the table has no Price field); the image (the cell's picture, else the product's, in the box's image link); the
 * Primary Button (the cell's text, else the box's, with the box's link: the live public link); the Secondary Button
 * (the cell's address, nothing without one); Pros and Cons; the description's HTML; the lists; Copy to Clipboard; the
 * price (an Amazon product's live price, else the cell's; blank "N/A"; the Prime logo); any other value as text with
 * "Yes" green and "No" red. The name label shows as TableCell::labelled() says (R49).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Shortcode\TableCell $cell
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Product\ProductType;

$table_field = $cell->field;
$table_value = $cell->value;
$table_label = $cell->label ? '<div class="field-name"><strong>' . esc_html( $table_field->name ) . ':</strong></div>' : '';
$table_lines = array_values( array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $table_value ) ) ), static fn( string $line ): bool => '' !== $line ) );
?>
<?php if ( FieldType::Rating === $table_field->type ) : ?>
	<?php $table_rating = is_numeric( trim( $table_value ) ) ? trim( $table_value ) : '3.5'; ?>
	<?php echo $table_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the name is escaped above. ?>
	<div data-tooltip="<?php echo esc_attr( $table_field->name ); ?>">
		<div class="affibloom-stars" style="--rating: <?php echo esc_attr( $table_rating ); ?>">
			<span class="affibloom-stars-value"><?php echo esc_html( number_format( (float) $table_rating, 1, '.', '' ) ); ?></span>
		</div>
	</div>
<?php else : ?>
	<div class="affibloom-fields">
		<?php if ( FieldType::ProductName === $table_field->type ) : ?>
			<?php if ( $cell->box->title_linked ) : ?>
				<?php $attributes = $cell->box->link; ?>
				<a class="product-name affibloom-title"<?php require __DIR__ . '/attributes.php'; ?>><?php echo wp_kses_post( $table_value ); ?></a>
			<?php else : ?>
				<h3 class="product-name affibloom-title"><?php echo wp_kses_post( $table_value ); ?></h3>
			<?php endif; ?>
			<?php if ( $cell->prime_in_name ) : ?>
				<i class="affibloom-amazon-prime"></i>
			<?php endif; ?>
		<?php elseif ( FieldType::Image === $table_field->type ) : ?>
			<?php $attributes = $cell->box->link; ?>
			<div class="img-container">
				<a<?php require __DIR__ . '/attributes.php'; ?>>
					<img class="affibloom-image-no-lazy" src="<?php echo esc_url( '' !== $table_value ? $table_value : $cell->box->image_src ); ?>" <?php echo \Affibloom\Interface\Front\ImageLoading::attribute(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- one of three fixed attributes. ?> alt="<?php echo esc_attr( $cell->box->name ); ?>"/>
				</a>
			</div>
		<?php elseif ( FieldType::Button === $table_field->type ) : ?>
			<?php
			$table_button = json_decode( $table_value, true );
			$table_button = is_array( $table_button ) ? $table_button : array();
			$table_text   = is_string( $table_button['button_text'] ?? null ) ? $table_button['button_text'] : '';
			$table_url    = is_string( $table_button['url'] ?? null ) ? trim( $table_button['url'] ) : '';
			$table_box    = $cell->box->buttons;
			?>
			<div class="btn-container">
				<?php if ( \Affibloom\Domain\Table\StartingLayout::PRIMARY_BUTTON === $table_field->id && isset( $table_box[0] ) ) : ?>
					<?php $attributes = $table_box[0]->attributes; ?>
					<a class="btn affibloom-button-1"<?php require __DIR__ . '/attributes.php'; ?>>
						<?php echo wp_kses_post( '' !== trim( $table_text ) ? $table_text : $table_box[0]->text ); ?>
					</a>
				<?php elseif ( \Affibloom\Domain\Table\StartingLayout::PRIMARY_BUTTON !== $table_field->id && '' !== $table_url ) : ?>
					<?php
					// R50: the second button's attributes (`data-affibloom-btn="2"`, its own target and rel) with the cell's address.
					$attributes         = isset( $table_box[1] ) ? $table_box[1]->attributes : ( isset( $table_box[0] ) ? $table_box[0]->attributes + array( 'data-affibloom-btn' => '2' ) : array() );
					$attributes['href'] = $table_url;
					?>
					<a class="btn affibloom-button-2"<?php require __DIR__ . '/attributes.php'; ?>>
						<?php echo wp_kses_post( $table_text ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php elseif ( in_array( $table_field->id, array( 2, 3 ), true ) ) : ?>
			<div class="affibloom-fields-<?php echo esc_attr( sanitize_title( $table_field->name ) ); ?> affibloom-fields-<?php echo esc_attr( (string) $table_field->id ); ?>">
				<?php echo $table_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the name is escaped above. ?>
				<?php if ( array() !== $table_lines ) : ?>
					<ul>
						<?php foreach ( $table_lines as $table_line ) : ?>
							<?php if ( 2 === $table_field->id ) : ?>
								<li><span class="affibloom-check"><span class="affibloom-check-content"></span></span><?php echo esc_html( $table_line ); ?></li>
							<?php else : ?>
								<li><span class="affibloom-x"><span class="affibloom-x-1"></span><span class="affibloom-x-2"></span></span><?php echo esc_html( $table_line ); ?></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php elseif ( FieldType::Editor === $table_field->type ) : ?>
			<div class="description-content">
				<div class="affibloom-fields-single">
					<?php echo $table_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the name is escaped above. ?>
					<div class="field-value">
						<span><?php echo wp_kses_post( $table_value ); ?></span>
					</div>
				</div>
			</div>
		<?php elseif ( FieldType::BulletedList === $table_field->type || FieldType::NumberedList === $table_field->type ) : ?>
			<?php $table_tag = FieldType::BulletedList === $table_field->type ? 'ul' : 'ol'; ?>
			<div class="affibloom-fields-<?php echo esc_attr( sanitize_title( $table_field->name ) ); ?> affibloom-fields-<?php echo esc_attr( (string) $table_field->id ); ?>">
				<?php echo $table_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the name is escaped above. ?>
				<?php if ( array() !== $table_lines ) : ?>
					<<?php echo tag_escape( $table_tag ); ?> class="list">
						<?php foreach ( $table_lines as $table_line ) : ?>
							<li><?php echo esc_html( $table_line ); ?></li>
						<?php endforeach; ?>
					</<?php echo tag_escape( $table_tag ); ?>>
				<?php endif; ?>
			</div>
		<?php elseif ( FieldType::CopyClipboard === $table_field->type ) : ?>
			<div class="affibloom-fields-single affibloom-field-copy-clipboard">
				<?php echo $table_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the name is escaped above. ?>
				<div class="field-value">
					<?php if ( '' !== $table_value ) : ?>
						<span class="affibloom-copy-clipboard-value"><?php echo esc_html( $table_value ); ?></span><button type="button" class="affibloom-copy-clipboard-btn" aria-label="<?php esc_attr_e( 'Copy to clipboard', 'affibloom' ); ?>" data-copy-value="<?php echo esc_attr( $table_value ); ?>"><svg class="affibloom-copy-clipboard-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg></button>
					<?php endif; ?>
				</div>
			</div>
		<?php elseif ( FieldType::Label === $table_field->type ) : ?>
			<?php
			echo $table_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the name is escaped above.
			$table_amazon = ProductType::Amazon === $cell->product->type && '' !== $cell->product->asin;
			$table_price  = $table_amazon ? $cell->product->price : $table_value;
			$table_price  = '' === trim( wp_strip_all_tags( $table_price ) ) ? __( 'N/A', 'affibloom' ) : $table_price;
			?>
			<div class="affibloom-price">
				<div class="affibloom-price-value"><span class="affibloom-discount-price"><?php echo $table_amazon ? wp_kses_post( $cell->box->discount ) : ''; ?></span><span class="affibloom-latest-price"><?php echo wp_kses_post( $table_price ); ?></span></div>
				<?php if ( $cell->box->prime ) : ?>
					<i class="affibloom-amazon-prime"></i>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<?php
			$table_class = 0 === strcasecmp( trim( $table_value ), 'Yes' ) ? 'green' : ( 0 === strcasecmp( trim( $table_value ), 'No' ) ? 'red' : '' );
			?>
			<div class="affibloom-fields-single">
				<?php echo $table_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the name is escaped above. ?>
				<div class="field-value">
					<span class="<?php echo esc_attr( $table_class ); ?>"><?php echo nl2br( wp_kses_post( $table_value ) ); ?></span>
				</div>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
