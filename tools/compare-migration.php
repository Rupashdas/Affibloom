<?php
/**
 * Compares each migrated product's box with Lasso's baseline (F10 AC24; Task 26P).
 *
 * Run on Local: `bash tools/local-wp.sh eval-file tools/compare-migration.php`. For each of the 250 boxes in
 * tests/fixtures/baselines/boxes.json (what Lasso's box showed on the live site), it draws Affibloom's box for the
 * product the migration made from that Lasso record, with the same shortcode attributes
 * (tests/fixtures/inventory/boxes.json), and compares the values: title, badge, description, image, rating, Prime,
 * disclosure, and each link's href, text, rel and target. It prints each difference, a count per field, and
 * "250 boxes: N equal, N different". The shortcode's `lasso-br-code` is rewritten to Affibloom's `-br-`, as content
 * conversion (LM2) does.
 *
 * By default it draws the product the migration linked to the record (on Local, many were adopted from the one-time
 * import with their own values: 26A). With `fresh` (`… eval-file tools/compare-migration.php fresh`) it draws a product
 * made just now from the record by the products family's mapper through Affibloom's API, as a migration on a site
 * without Affibloom does, and deletes it afterwards; nothing else is written.
 *
 * @package Affibloom
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.DB.DirectDatabaseQuery, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- a WP-CLI tool printing to the terminal; DOM's own property names.

$affibloom_root      = dirname( __DIR__ );
$affibloom_baselines = json_decode( (string) file_get_contents( $affibloom_root . '/tests/fixtures/baselines/boxes.json' ), true );
$affibloom_inventory = json_decode( (string) file_get_contents( $affibloom_root . '/tests/fixtures/inventory/boxes.json' ), true );

/**
 * Whitespace collapsed, entities decoded.
 *
 * @param string $text Text.
 */
function affibloom_compare_text( string $text ): string {
	return trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/**
 * A link's values, or null when there is none.
 *
 * @param DOMXPath $xpath The box.
 * @param string   $css_class The link's class.
 * @return array{href: string, text: string, rel: string, target: string}|null
 */
function affibloom_compare_link( DOMXPath $xpath, string $css_class ): ?array {
	$node = $xpath->query( "//a[contains(concat(' ', normalize-space(@class), ' '), ' $css_class ')]" )->item( 0 );
	if ( ! $node instanceof DOMElement ) {
		return null;
	}

	return array(
		'href'   => $node->getAttribute( 'href' ),
		'text'   => affibloom_compare_text( $node->textContent ),
		'rel'    => $node->getAttribute( 'rel' ),
		'target' => $node->getAttribute( 'target' ),
	);
}

/**
 * The values Affibloom's box shows, in the baseline's keys.
 *
 * @param string $html The box.
 * @return array<string, mixed>
 */
function affibloom_compare_values( string $html ): array {
	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>' );
	libxml_clear_errors();
	$xpath = new DOMXPath( $document );
	$text  = static function ( string $css_class ) use ( $xpath ): string {
		$node = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' $css_class ')]" )->item( 0 );

		return null === $node ? '' : affibloom_compare_text( $node->textContent );
	};
	$image = $xpath->query( "//a[contains(@class, 'affibloom-image')]//img" )->item( 0 );
	$stars = $xpath->query( "//*[contains(@class, 'affibloom-stars')]" )->item( 0 );
	$link  = static fn ( ?array $values ): ?array => $values;

	return array(
		'title'       => $text( 'affibloom-title' ),
		'badge'       => $text( 'affibloom-badge-text' ),
		'description' => $text( 'affibloom-description' ),
		'image'       => $image instanceof DOMElement ? $image->getAttribute( 'src' ) : '',
		'rating'      => $stars instanceof DOMElement && preg_match( '/--rating:\s*([\d.]+)/', $stars->getAttribute( 'style' ), $found ) ? $found[1] : '',
		'prime'       => $xpath->query( "//*[contains(@class, 'affibloom-amazon-prime')]" )->length > 0,
		'disclosure'  => $text( 'affibloom-disclosure' ),
		'button1'     => $link( affibloom_compare_link( $xpath, 'affibloom-button-1' ) ),
		'button2'     => $link( affibloom_compare_link( $xpath, 'affibloom-button-2' ) ),
		'title_link'  => $link( affibloom_compare_link( $xpath, 'affibloom-title' ) ),
		'image_link'  => $link( affibloom_compare_link( $xpath, 'affibloom-image' ) ),
	);
}

/**
 * A product made now from a Lasso record, as the products family makes it; 0 when the record gives none.
 *
 * @param int       $lasso_id The record.
 * @param list<int> $made     The products made, filled in (one per record).
 */
function affibloom_compare_fresh( int $lasso_id, array &$made ): int {
	static $by_record = array();
	if ( isset( $by_record[ $lasso_id ] ) ) {
		return $by_record[ $lasso_id ];
	}
	$record = ( new AffibloomLassoMigrator\Infrastructure\Lasso\WpLassoSource() )->records( array( $lasso_id ) )[0] ?? null;
	$fields = null === $record ? null : ( new AffibloomLassoMigrator\Application\LassoRecordMapper( site_url() ) )->fields( $record );
	// The API Affibloom hands helpers, read from its own plugins_loaded listener (a tool: firing it again would start
	// every helper twice).
	$api = null;
	foreach ( $GLOBALS['wp_filter']['plugins_loaded']->callbacks as $listeners ) {
		foreach ( $listeners as $listener ) {
			if ( is_array( $listener['function'] ) && $listener['function'][0] instanceof Affibloom\Extension\ReadyAction ) {
				$api = ( new ReflectionProperty( Affibloom\Extension\ReadyAction::class, 'api' ) )->getValue( $listener['function'][0] );
			}
		}
	}
	if ( null === $fields || null === $api ) {
		$by_record[ $lasso_id ] = 0;

		return 0;
	}
	try {
		$by_record[ $lasso_id ] = $api->import_product( $fields );
		$made[]                 = $by_record[ $lasso_id ];
	} catch ( InvalidArgumentException $refused ) {
		$by_record[ $lasso_id ] = 0;
	}

	return $by_record[ $lasso_id ];
}

global $wpdb;
$affibloom_fresh  = in_array( 'fresh', (array) ( $args ?? array() ), true );
$affibloom_made   = array();
$affibloom_equal  = 0;
$affibloom_used   = array();
$affibloom_fields = array();
foreach ( $affibloom_baselines as $affibloom_index => $affibloom_lasso ) {
	$affibloom_where   = $affibloom_lasso['guide'] . ' #' . $affibloom_lasso['index'] . ' (Lasso ' . $affibloom_lasso['lasso_id'] . ')';
	$affibloom_product = $affibloom_fresh ? affibloom_compare_fresh( (int) $affibloom_lasso['lasso_id'], $affibloom_made ) : (int) $wpdb->get_var( $wpdb->prepare( "SELECT m.post_id FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_status = 'publish' WHERE m.meta_key = '_affibloom_lasso_id' AND m.meta_value = %s ORDER BY m.post_id LIMIT 1", $affibloom_lasso['lasso_id'] ) );
	if ( 0 === $affibloom_product ) {
		$affibloom_fields['product'] = ( $affibloom_fields['product'] ?? 0 ) + 1;
		WP_CLI::line( "$affibloom_where: no published product" );
		continue;
	}
	// The box's own shortcode: the guide's next unused box of this Lasso record (the two fixtures' orders differ).
	$affibloom_found = null;
	foreach ( $affibloom_inventory as $affibloom_at => $affibloom_box ) {
		if ( ! isset( $affibloom_used[ $affibloom_at ] ) && $affibloom_box['post_slug'] === $affibloom_lasso['guide'] && (string) ( $affibloom_box['shortcode']['id'] ?? '' ) === (string) $affibloom_lasso['lasso_id'] ) {
			$affibloom_used[ $affibloom_at ] = true;
			$affibloom_found                 = $affibloom_box;
			break;
		}
	}
	$affibloom_attributes = array_diff_key( (array) ( $affibloom_found['shortcode'] ?? array() ), array_flip( array( 'id', 'link_id', 'ref' ) ) );
	$affibloom_attributes = array_map( static fn ( $value ) => str_replace( 'lasso-br-code', '-br-', (string) $value ), $affibloom_attributes );
	$affibloom_code       = '[affibloom id="' . $affibloom_product . '"';
	foreach ( $affibloom_attributes as $affibloom_key => $affibloom_value ) {
		$affibloom_code .= ' ' . sanitize_key( (string) $affibloom_key ) . '="' . str_replace( '"', '&quot;', (string) $affibloom_value ) . '"';
	}
	$affibloom_ours = affibloom_compare_values( do_shortcode( $affibloom_code . ']' ) );
	$affibloom_diff = array();
	foreach ( $affibloom_ours as $affibloom_key => $affibloom_value ) {
		$affibloom_want = $affibloom_lasso[ $affibloom_key ] ?? null;
		if ( is_array( $affibloom_want ) || is_array( $affibloom_value ) ) {
			foreach ( array( 'href', 'text', 'rel', 'target' ) as $affibloom_part ) {
				$affibloom_a = affibloom_compare_text( (string) ( $affibloom_want[ $affibloom_part ] ?? '' ) );
				$affibloom_b = affibloom_compare_text( (string) ( $affibloom_value[ $affibloom_part ] ?? '' ) );
				if ( $affibloom_a !== $affibloom_b ) {
					$affibloom_diff[ "$affibloom_key.$affibloom_part" ] = array( $affibloom_a, $affibloom_b );
				}
			}
			continue;
		}
		$affibloom_a = is_bool( $affibloom_want ) ? ( $affibloom_want ? 'yes' : 'no' ) : affibloom_compare_text( (string) $affibloom_want );
		$affibloom_b = is_bool( $affibloom_value ) ? ( $affibloom_value ? 'yes' : 'no' ) : affibloom_compare_text( (string) $affibloom_value );
		if ( $affibloom_a !== $affibloom_b ) {
			$affibloom_diff[ $affibloom_key ] = array( $affibloom_a, $affibloom_b );
		}
	}
	if ( array() === $affibloom_diff ) {
		++$affibloom_equal;
		continue;
	}
	foreach ( $affibloom_diff as $affibloom_key => $affibloom_pair ) {
		$affibloom_fields[ $affibloom_key ] = ( $affibloom_fields[ $affibloom_key ] ?? 0 ) + 1;
		WP_CLI::line( "$affibloom_where $affibloom_key: Lasso \"" . $affibloom_pair[0] . '" / ours "' . $affibloom_pair[1] . '"' );
	}
}
foreach ( $affibloom_made as $affibloom_temporary ) {
	wp_delete_post( $affibloom_temporary, true );
}
arsort( $affibloom_fields );
foreach ( $affibloom_fields as $affibloom_key => $affibloom_count ) {
	WP_CLI::line( "  $affibloom_key: $affibloom_count boxes" );
}
WP_CLI::line( sprintf( '%d boxes: %d equal, %d different', count( $affibloom_baselines ), $affibloom_equal, count( $affibloom_baselines ) - $affibloom_equal ) );
