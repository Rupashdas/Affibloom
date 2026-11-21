<?php
/**
 * Settings → General, Uninstall (F37 R14-R19, N1; F9 R63-R65, AC16; Task 38, TL5): the opt-in that lets deleting the
 * plugin remove its data, and the posts that still use Affibloom. Lasso's card offered "Uninstall Lasso", which rewrote
 * every post; that is not built (decision A8): nothing here rewrites a post. Deactivating removes nothing.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings, uninstall: array{ids: list<int>, total: int}} $view
 */

defined( 'ABSPATH' ) || exit;

$using  = $view['uninstall']['ids'];
$others = $view['uninstall']['total'] - count( $using );
?>
<div class="affibloom-card" id="affibloom-card-uninstall">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Uninstall', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Deactivating Affibloom keeps everything. Deleting it keeps everything too, unless you turn this on.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php
		$this->render(
			'admin/partials/switch-row',
			array(
				'id'      => 'affibloom-setting-delete_data_on_uninstall',
				'name'    => 'settings[delete_data_on_uninstall]',
				'label'   => __( 'Delete all Affibloom data when the plugin is deleted', 'affibloom' ),
				'checked' => $view['settings']->bool( 'delete_data_on_uninstall' ),
				'note'    => __( 'Products, click counts and link checks are removed for good. Export them first.', 'affibloom' ),
			)
		);
		?>
		<div class="affibloom-uninstall-posts">
			<h3 class="affibloom-uninstall-posts__title"><?php esc_html_e( 'Posts that still use Affibloom', 'affibloom' ); ?></h3>
			<?php if ( array() === $using ) : ?>
				<p class="affibloom-uninstall-posts__none"><?php esc_html_e( 'No post uses Affibloom.', 'affibloom' ); ?></p>
			<?php else : ?>
				<p class="affibloom-field__hint"><?php esc_html_e( 'Their boxes and links stop showing once the data is removed. Nothing changes the posts themselves.', 'affibloom' ); ?></p>
				<ul class="affibloom-uninstall-posts__list">
					<?php foreach ( $using as $using_id ) : ?>
						<li><a href="<?php echo esc_url( (string) get_edit_post_link( $using_id ) ); ?>"><?php echo esc_html( '' === get_the_title( $using_id ) ? __( '(no title)', 'affibloom' ) : get_the_title( $using_id ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $others > 0 ) : ?>
					<?php /* translators: %s: number of further posts. */ ?>
					<p class="affibloom-uninstall-posts__more"><?php echo esc_html( sprintf( _n( 'And %s more post.', 'And %s more posts.', $others, 'affibloom' ), number_format_i18n( $others ) ) ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
