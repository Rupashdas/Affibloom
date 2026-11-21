<?php
/**
 * An Opportunities screen's intro box (F22 R5, R6, D7, D8; Task O1; Lasso's education boxes): its heading and text,
 * and × that closes it for good (POST /opportunities/intros/{box}). Lasso's help link is left out (deviation 52).
 *
 * @package Affibloom
 * @var array{box: string, title: string, text: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="affibloom-intro" data-intro="<?php echo esc_attr( $view['box'] ); ?>" aria-labelledby="affibloom-intro-<?php echo esc_attr( $view['box'] ); ?>">
	<span class="affibloom-chip affibloom-intro__chip" aria-hidden="true"><?php $this->render( 'admin/partials/icon', array( 'name' => 'bulb' ) ); ?></span>
	<div class="affibloom-intro__text">
		<h2 class="affibloom-intro__title" id="affibloom-intro-<?php echo esc_attr( $view['box'] ); ?>"><?php echo esc_html( $view['title'] ); ?></h2>
		<p><?php echo esc_html( $view['text'] ); ?></p>
	</div>
	<button type="button" class="affibloom-intro__close" data-intro-close="<?php echo esc_attr( $view['box'] ); ?>" aria-label="<?php esc_attr_e( 'Close this box for good', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
	</button>
</section>
