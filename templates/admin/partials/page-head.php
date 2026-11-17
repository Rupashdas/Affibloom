<?php
/**
 * The head of every Affibloom screen (Meadow, Task MD3; design system 0b.4 "Page head"): on the page background, the
 * title with what belongs under it (the U10 subtitle, a link, the screen's section tabs) on the left and the screen's
 * head actions on the right, level with the title. An optional partial runs under the subtitle (`meta`: the Dashboard's
 * summary line, Task MD4) and another under the whole row (`below`: the Dashboard's search). It replaces the gradient
 * band the screens printed one by one. A product page (Task MD5) adds a way back above the title (`back`), its image
 * left of it (`image`, a URL or '' for none) and the full title as a tooltip (`tooltip`), as its name may be cut.
 *
 * @package Affibloom
 * @var array{title: string, title_id?: string, title_class?: string, tooltip?: bool, back?: array{url: string, label: string}, image?: string, subtitle?: string, link?: string, meta?: string, meta_args?: array<string, mixed>, tabs?: array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int, show_zero?: bool}>}, actions?: string, actions_args?: array<string, mixed>, below?: string, below_args?: array<string, mixed>} $view
 */

defined( 'ABSPATH' ) || exit;

$head_title_class = trim( 'affibloom-page__title ' . ( $view['title_class'] ?? '' ) );
?>
<div class="affibloom-page__head">
	<?php if ( isset( $view['back'] ) ) : ?>
		<a class="affibloom-page__back" href="<?php echo esc_url( $view['back']['url'] ); ?>"><span aria-hidden="true">‹</span> <?php echo esc_html( $view['back']['label'] ); ?></a>
	<?php endif; ?>
	<div class="affibloom-page__head-row">
		<?php if ( isset( $view['image'] ) ) : ?>
			<?php // The product's picture as stored (product-save.js swaps it after a save); the preview shows it too, so the tile is not announced. ?>
			<span class="affibloom-page__image" aria-hidden="true">
				<?php // No empty src: the browser would fetch the page itself. ?>
				<img<?php echo '' === $view['image'] ? ' hidden' : ' src="' . esc_url( $view['image'] ) . '"'; ?> alt="" width="76" height="76" data-head-image>
				<span class="affibloom-page__image-none" data-head-image-none<?php echo '' === $view['image'] ? '' : ' hidden'; ?>><?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?></span>
			</span>
		<?php endif; ?>
		<div class="affibloom-page__head-text">
			<h1 class="<?php echo esc_attr( $head_title_class ); ?>"<?php echo isset( $view['title_id'] ) ? ' id="' . esc_attr( $view['title_id'] ) . '"' : ''; ?><?php echo empty( $view['tooltip'] ) ? '' : ' title="' . esc_attr( $view['title'] ) . '"'; ?>><?php echo esc_html( $view['title'] ); ?></h1>
			<?php if ( isset( $view['link'] ) ) : ?>
				<?php // The product's link under its name (D5); product-save.js updates it after a save. ?>
				<a class="affibloom-product-url" href="<?php echo esc_url( $view['link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $view['link'] ); ?></a>
			<?php endif; ?>
			<?php if ( isset( $view['subtitle'] ) ) : ?>
				<p class="affibloom-page__subtitle"><?php echo esc_html( $view['subtitle'] ); ?></p>
			<?php endif; ?>
			<?php if ( isset( $view['meta'] ) ) : ?>
				<?php $this->render( $view['meta'], $view['meta_args'] ?? array() ); ?>
			<?php endif; ?>
			<?php if ( isset( $view['tabs'] ) ) : ?>
				<?php $this->render( 'admin/partials/section-tabs', $view['tabs'] ); ?>
			<?php endif; ?>
		</div>
		<?php if ( isset( $view['actions'] ) ) : ?>
			<div class="affibloom-page__actions">
				<?php $this->render( $view['actions'], $view['actions_args'] ?? array() ); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	if ( isset( $view['below'] ) ) {
		$this->render( $view['below'], $view['below_args'] ?? array() );
	}
	?>
</div>
