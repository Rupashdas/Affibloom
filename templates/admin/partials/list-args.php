<?php
/**
 * Hidden inputs for the Dashboard's GET forms (Task 13F; F19 D17): the screen's `page` and every list argument the
 * form does not set itself, so a search, a page size or the filters' Apply keeps all the others without JavaScript.
 * The arguments are DashboardUrl::args() of the list on screen, at page 1: any change starts the list again there.
 *
 * @package Affibloom
 * @var array{args: array<string, string>, except: list<string>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

?>
<input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::SLUG ); ?>">
<?php foreach ( array_diff_key( $view['args'], array_flip( $view['except'] ) ) as $name => $value ) : ?>
	<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
<?php endforeach; ?>
