<?php
/**
 * The product page's Amazon Data card (C1Q5): what is stored from Amazon for this product's ASIN and store, read-only.
 * The stored row only (deviation 122): Amazon's title, price and list price, saving, currency, availability, image,
 * features, source and last update. Prices in the box come with C1F (Phase 3).
 *
 * @package Affibloom
 * @var array{row: \Affibloom\Domain\Amazon\AmazonProduct|null} $view
 */

defined( 'ABSPATH' ) || exit;

$row  = $view['row'];
$none = '—';
?>
<section class="affibloom-card affibloom-amazon-data" aria-labelledby="affibloom-amazon-data-title">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title" id="affibloom-amazon-data-title"><?php esc_html_e( 'Amazon Data', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'What Affibloom stores from Amazon for this product, kept current every hour. Read-only.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
	<?php if ( null === $row ) : ?>
		<p class="affibloom-amazon-data__empty"><?php esc_html_e( 'No Amazon data yet. Refresh, or Sync Amazon data, fetches it.', 'affibloom' ); ?></p>
	<?php else : ?>
		<div class="affibloom-amazon-data__body">
			<?php if ( '' !== $row->image ) : ?>
				<img class="affibloom-amazon-data__image" src="<?php echo esc_url( $row->image ); ?>" alt="<?php esc_attr_e( 'Amazon image', 'affibloom' ); ?>" width="96" height="96" loading="lazy" decoding="async">
			<?php endif; ?>
			<dl class="affibloom-amazon-data__list">
				<dt><?php esc_html_e( 'Amazon title', 'affibloom' ); ?></dt>
				<dd><?php echo esc_html( '' === $row->name ? $none : $row->name ); ?></dd>
				<dt><?php esc_html_e( 'Price', 'affibloom' ); ?></dt>
				<dd><?php echo esc_html( '' === $row->price ? $none : $row->price ); ?></dd>
				<dt><?php esc_html_e( 'List price', 'affibloom' ); ?></dt>
				<dd><?php echo esc_html( '' === $row->savings_basis ? $none : $row->savings_basis ); ?></dd>
				<dt><?php esc_html_e( 'Saving', 'affibloom' ); ?></dt>
				<dd><?php echo esc_html( '' === $row->savings_amount ? $none : $row->savings_amount . ( $row->savings_percent > 0 ? ' (' . $row->savings_percent . '%)' : '' ) ); ?></dd>
				<dt><?php esc_html_e( 'Currency', 'affibloom' ); ?></dt>
				<dd><?php echo esc_html( $row->currency ); ?></dd>
				<dt><?php esc_html_e( 'Availability', 'affibloom' ); ?></dt>
				<dd><?php echo $row->out_of_stock ? esc_html__( 'Out of stock', 'affibloom' ) : esc_html__( 'In stock', 'affibloom' ); ?></dd>
				<dt><?php esc_html_e( 'Features', 'affibloom' ); ?></dt>
				<dd><?php echo esc_html( (string) count( $row->features ) ); ?></dd>
				<dt><?php esc_html_e( 'Source', 'affibloom' ); ?></dt>
				<dd><?php echo 'lasso' === $row->source ? esc_html__( 'Imported', 'affibloom' ) : esc_html__( 'Creators API', 'affibloom' ); ?></dd>
				<dt><?php esc_html_e( 'Last updated', 'affibloom' ); ?></dt>
				<dd><?php echo esc_html( substr( $row->last_updated, 0, 16 ) . ' UTC' ); ?></dd>
			</dl>
		</div>
	<?php endif; ?>
	</div>
</section>
