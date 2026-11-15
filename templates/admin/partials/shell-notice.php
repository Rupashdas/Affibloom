<?php
/**
 * One notice in the region under the header (design system, "Notice"; Task ON2): an icon chip in the tone's colour,
 * the words, the link where the words have %s, and a × when the notice has one (posting to the notice's route, when it
 * has one, so the dismissal is kept). The region's script template
 * (partials/notices.php) holds the same markup, so a notice a script adds looks the same.
 *
 * @package Affibloom
 * @var array{notice: \Affibloom\Domain\Shell\ShellNotice} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Shell\ShellNotice;
use Affibloom\Interface\NoticeText;

$shell_notice = $view['notice'];
$shell_words  = NoticeText::for( $shell_notice );
$shell_icons  = array(
	ShellNotice::SUCCESS => 'check',
	ShellNotice::ERROR   => 'close',
	ShellNotice::WARNING => 'warning',
	ShellNotice::UPDATE  => 'refresh',
);
// The link goes where the words say %s; without a link the words are printed as they are.
$shell_parts = '' !== $shell_notice->action_url ? explode( '%s', $shell_words['text'], 2 ) : array( $shell_words['text'] );
?>
<div class="affibloom-notice affibloom-notice--<?php echo esc_attr( $shell_notice->tone ); ?>">
	<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $shell_icons[ $shell_notice->tone ] ) ); ?></span>
	<p class="affibloom-notice__text">
		<?php echo esc_html( $shell_parts[0] ); ?>
		<?php if ( 2 === count( $shell_parts ) ) : ?>
			<a class="affibloom-notice__link" href="<?php echo esc_url( $shell_notice->action_url ); ?>"><?php echo esc_html( $shell_words['action'] ); ?></a><?php echo esc_html( $shell_parts[1] ); ?>
		<?php endif; ?>
	</p>
	<?php if ( $shell_notice->dismissible ) : ?>
		<button type="button" class="affibloom-notice__close" data-affibloom-dismiss aria-label="<?php esc_attr_e( 'Dismiss this notice', 'affibloom' ); ?>"
			<?php echo '' !== $shell_notice->dismiss_path ? 'data-dismiss-path="' . esc_attr( $shell_notice->dismiss_path ) . '"' : ''; ?>>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
	<?php endif; ?>
</div>
