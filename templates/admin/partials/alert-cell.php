<?php
/**
 * A Dashboard row's Alert cell (F11 R22, D13; Lasso's url-row.php 112-158; Task 31A), in Affibloom's look: the red
 * unlink "Broken since {date}" and the orange box "Out of stock since {date}" link to the product, with the days line
 * under them; the green tick "Link status checked. No alert issues detected."; the grey clock "Processing. Please
 * check back in 24 hours."; or nothing. Each icon carries its tooltip as visible-on-focus text, so the keyboard reaches
 * it too (the links are focusable; the tick and the clock take the focus as well). F22's lightbulb joins in its task.
 *
 * @package Affibloom
 * @var array{alert: \Affibloom\Domain\LinkHealth\AlertState, product_page: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\LinkHealth\AlertState;

$alert = $view['alert'];
/* translators: %d: days since the issue was found, the first day counting as 1. */
$days = sprintf( _n( '%d day', '%d days', $alert->days, 'affibloom' ), $alert->days );

switch ( $alert->kind ) {
	case AlertState::BROKEN:
	case AlertState::OUT_OF_STOCK:
		$broken = AlertState::BROKEN === $alert->kind;
		$tip    = $broken
			/* translators: %s: date, e.g. "Nov 16, 2026". */
			? sprintf( __( 'Broken since %s', 'affibloom' ), $alert->since() )
			/* translators: %s: date, e.g. "Nov 16, 2026". */
			: sprintf( __( 'Out of stock since %s', 'affibloom' ), $alert->since() );
		?>
		<a class="affibloom-alert affibloom-alert--<?php echo esc_attr( $alert->kind ); ?>" href="<?php echo esc_url( $view['product_page'] ); ?>" data-tip="<?php echo esc_attr( $tip ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => $broken ? 'unlink' : 'box' ) ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $tip ); ?></span>
		</a>
		<span class="affibloom-alert__days"><?php echo esc_html( $days ); ?></span>
		<?php
		break;
	case AlertState::CHECKED:
		$tip = __( 'Link status checked. No alert issues detected.', 'affibloom' );
		?>
		<span class="affibloom-alert affibloom-alert--checked" role="status" tabindex="0" data-tip="<?php echo esc_attr( $tip ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'check' ) ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $tip ); ?></span>
		</span>
		<?php
		break;
	case AlertState::PENDING:
		$tip = __( 'Processing. Please check back in 24 hours.', 'affibloom' );
		?>
		<span class="affibloom-alert affibloom-alert--pending" tabindex="0" data-tip="<?php echo esc_attr( $tip ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'clock' ) ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $tip ); ?></span>
		</span>
		<?php
		break;
}
