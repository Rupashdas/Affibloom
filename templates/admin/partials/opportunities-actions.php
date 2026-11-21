<?php
/**
 * The Opportunities lists' head action (F22 R4; Task O1): Lasso's "Search", level with the title. Without JavaScript
 * it is a form that reloads the list; opportunities.js redraws it in place instead. It keeps the product filter.
 *
 * @package Affibloom
 * @var array{query: \Affibloom\Domain\Opportunity\OpportunityQuery, domain?: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\OpportunitiesScreen;

$query = $view['query'];
?>
<form class="affibloom-page__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
	<input type="hidden" name="page" value="<?php echo esc_attr( OpportunitiesScreen::SLUG ); ?>">
	<?php if ( 'links' !== $query->kind ) : ?>
		<input type="hidden" name="tab" value="<?php echo esc_attr( $query->kind ); ?>">
	<?php endif; ?>
	<?php if ( '' !== ( $view['domain'] ?? '' ) ) : ?>
		<input type="hidden" name="domain" value="<?php echo esc_attr( $view['domain'] ); ?>">
	<?php endif; ?>
	<?php if ( $query->product > 0 ) : ?>
		<input type="hidden" name="product" value="<?php echo esc_attr( (string) $query->product ); ?>">
	<?php endif; ?>
	<label class="screen-reader-text" for="affibloom-opportunities-search"><?php esc_html_e( 'Search Opportunities', 'affibloom' ); ?></label>
	<input type="search" id="affibloom-opportunities-search" class="affibloom-input affibloom-search" name="s"
		value="<?php echo esc_attr( $query->search ); ?>" placeholder="<?php esc_attr_e( 'Search', 'affibloom' ); ?>">
</form>
