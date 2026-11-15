<?php
/**
 * The buttons (F5 R37): each a link with its text through the icon allowlist.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductInput;

foreach ( $view->buttons as $button ) :
	$attributes = $button->attributes;
	?>
	<a class="<?php echo esc_attr( $button->css_class ); ?>"<?php require __DIR__ . '/attributes.php'; ?>>
		<?php echo wp_kses( $button->text, ProductInput::ICON_MARKUP ); ?>
	</a>
	<?php
endforeach;
