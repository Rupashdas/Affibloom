<?php
/**
 * One place on the Locations tab (F12 L7; Lasso's link-display-row.php; Task 36B): Content (the host, left empty in a
 * group's nested rows), Link with Context, Type with Lasso's tooltip, and Clicks (a placeholder until 36C and 34C).
 *
 * @package Affibloom
 * @var array{host: \Affibloom\Domain\LinkIndex\LocationHost, place: \Affibloom\Domain\LinkIndex\ProductLocation, nested: bool} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\LinkIndex\ProductLocation;

$host        = $view['host'];
$place       = $view['place'];
$place_types = array(
	ProductLocation::TEXT    => array( 'link', __( 'This is a text link.', 'affibloom' ) ),
	ProductLocation::IMAGE   => array( 'image', __( 'This is an image link.', 'affibloom' ) ),
	ProductLocation::SINGLE  => array( 'box', __( 'This is a single link display.', 'affibloom' ) ),
	ProductLocation::GRID    => array( 'grid', __( 'This is a group of links display.', 'affibloom' ) ),
	ProductLocation::LIST    => array( 'list', __( 'This is a group of links display.', 'affibloom' ) ),
	ProductLocation::GALLERY => array( 'gallery', __( 'This is a group of links display.', 'affibloom' ) ),
);
$place_type  = $place_types[ $place->kind() ];
?>
<tr class="affibloom-location<?php echo $view['nested'] ? ' affibloom-location--nested' : ''; ?>"
	<?php echo $view['nested'] ? 'data-location-of="' . esc_attr( (string) $host->id ) . '" hidden' : ''; ?>>
	<td class="affibloom-col-content">
		<?php
		if ( ! $view['nested'] ) {
			$this->render( 'admin/partials/location-host', array( 'host' => $host ) );
		}
		?>
	</td>
	<td class="affibloom-col-link">
		<span class="affibloom-location__context<?php echo $place->is_display() ? ' affibloom-location__context--code' : ''; ?>" title="<?php echo esc_attr( $place->context() ); ?>"><?php echo esc_html( $place->context() ); ?></span>
	</td>
	<td class="affibloom-col-type">
		<span class="affibloom-alert affibloom-location__type" tabindex="0" data-tip="<?php echo esc_attr( $place_type[1] ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => $place_type[0] ) ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $place_type[1] ); ?></span>
		</span>
	</td>
	<td class="affibloom-col-clicks"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></td>
</tr>
