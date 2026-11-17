<?php
/**
 * The head of every Affibloom screen (Meadow, Task MD3; design system 0b.4 "Page head"): on the page background, the
 * title with what belongs under it (the U10 subtitle, a link, the screen's section tabs) on the left and the screen's
 * head actions on the right, level with the title. An optional partial runs under the subtitle (`meta`: the Dashboard's
 * summary line, Task MD4) and another under the whole row (`below`: the Dashboard's search). It replaces the gradient
 * band the screens printed one by one.
 *
 * @package Affibloom
 * @var array{title: string, title_id?: string, title_class?: string, subtitle?: string, link?: string, meta?: string, meta_args?: array<string, mixed>, tabs?: array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int}>}, actions?: string, actions_args?: array<string, mixed>, below?: string, below_args?: array<string, mixed>} $view
 */

defined( 'ABSPATH' ) || exit;

$head_title_class = trim( 'affibloom-page__title ' . ( $view['title_class'] ?? '' ) );
?>
<div class="affibloom-page__head">
	<div class="affibloom-page__head-row">
		<div class="affibloom-page__head-text">
			<h1 class="<?php echo esc_attr( $head_title_class ); ?>"<?php echo isset( $view['title_id'] ) ? ' id="' . esc_attr( $view['title_id'] ) . '"' : ''; ?>><?php echo esc_html( $view['title'] ); ?></h1>
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
