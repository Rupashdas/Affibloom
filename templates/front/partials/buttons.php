<?php
/**
 * Buttons (F5 R37): each a link with its text through the icon allowlist. The layout says which buttons it prints
 * here (all of them, or the 6-box layout's first and the rest apart).
 *
 * @package Affibloom
 * @var list<\Affibloom\Application\Box\BoxButton> $buttons
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductInput;

foreach ( $buttons as $button ) :
	$attributes = $button->attributes;
	?>
	<a class="<?php echo esc_attr( $button->css_class ); ?>"<?php require __DIR__ . '/attributes.php'; ?>>
		<?php echo wp_kses( $button->text, ProductInput::ICON_MARKUP ); ?>
	</a>
	<?php
endforeach;
