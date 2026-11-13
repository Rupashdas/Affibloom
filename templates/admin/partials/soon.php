<?php
/**
 * A placeholder for what a later task brings (the user, 2026-11-13: "use realistic placeholders such as 'Coming
 * Soon' … Don't omit columns or UI elements just because their underlying functionality isn't ready yet").
 *
 * - "chip": a small "Soon" label beside a column's name.
 * - "cell": a muted dash in a cell, read out as "Coming soon", so a column of them stays quiet to look at.
 *
 * Never a borrowed number: Lasso's own alerts, locations and clicks are not read at runtime (ADR-2).
 *
 * @package Affibloom
 * @var array{variant: string} $view
 */

defined( 'ABSPATH' ) || exit;

if ( 'chip' === $view['variant'] ) : ?>
	<span class="affibloom-soon" title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>"><?php esc_html_e( 'Soon', 'affibloom' ); ?></span>
<?php else : ?>
	<span class="affibloom-soon-cell" title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>">
		<span aria-hidden="true">—</span>
		<span class="screen-reader-text"><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
	</span>
	<?php
endif;
