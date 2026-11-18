<?php
/**
 * The product page's issue bars (F11 R29, D7, D17; Lasso's link_detail_notification.php; Task 31C): the red "Your URL
 * is broken." and the orange "This product may be out of stock.", both possible, each with a × that closes it for 7
 * days (product-save.js, POST …/alerts/dismiss); and, for an Amazon product while the Amazon API is not connected, a
 * quiet line saying it is not checked. Affibloom's notice style.
 *
 * @package Affibloom
 * @var array{issues: array{broken: bool, out_of_stock: bool, amazon_unchecked: bool}, product_id: int} $view
 */

defined( 'ABSPATH' ) || exit;

$issue_bars = array(
	array( 'broken', 'error', 'unlink', __( 'Your URL is broken.', 'affibloom' ) ),
	array( 'out-of-stock', 'warning', 'box', __( 'This product may be out of stock.', 'affibloom' ) ),
);
?>
<?php foreach ( $issue_bars as $issue_bar ) : ?>
	<?php
	if ( ! $view['issues'][ 'broken' === $issue_bar[0] ? 'broken' : 'out_of_stock' ] ) {
		continue;
	}
	?>
	<div class="affibloom-notice affibloom-notice--<?php echo esc_attr( $issue_bar[1] ); ?> affibloom-issue-bar" role="status" data-issue-bar="<?php echo esc_attr( $issue_bar[0] ); ?>">
		<span class="affibloom-chip affibloom-chip--<?php echo esc_attr( 'error' === $issue_bar[1] ? 'danger' : 'warning' ); ?>"><?php $this->render( 'admin/partials/icon', array( 'name' => $issue_bar[2] ) ); ?></span>
		<p class="affibloom-notice__text"><?php echo esc_html( $issue_bar[3] ); ?></p>
		<button type="button" class="affibloom-notice__close" data-alert-dismiss="<?php echo esc_attr( $issue_bar[0] ); ?>"
			data-product-id="<?php echo esc_attr( (string) $view['product_id'] ); ?>" aria-label="<?php esc_attr_e( 'Hide for 7 days', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
	</div>
<?php endforeach; ?>
<?php if ( $view['issues']['amazon_unchecked'] ) : ?>
	<p class="affibloom-issue-line"><?php esc_html_e( 'Amazon products are checked through the Amazon API, which is not connected (Settings → Amazon).', 'affibloom' ); ?></p>
<?php endif; ?>
