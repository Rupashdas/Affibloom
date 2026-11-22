<?php
/**
 * A page a helper plugin added through the extension API (Task LM1, `docs/extension-api.md`): Affibloom's header,
 * the page head with the helper's title and subtitle, the helper's body inside the page body, the footer.
 *
 * @package Affibloom
 * @var array{title: string, subtitle: string, body: \Closure(): void} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		$this->render(
			'admin/partials/page-head',
			array(
				'title'    => $view['title'],
				'subtitle' => $view['subtitle'],
			)
		);
		?>

		<div class="affibloom-page__body">
			<?php ( $view['body'] )(); ?>
		</div>
	</div>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
