<?php
/**
 * The badge (F5 R10): printed when it has text, through the icon allowlist (empty `span[class]` only).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductInput;

if ( '' !== $view->badge ) :
	?>
	<div class="affibloom-badge">
		<span class="affibloom-badge-text"><?php echo wp_kses( $view->badge, ProductInput::ICON_MARKUP ); ?></span>
	</div>
	<?php
endif;
