<?php
/**
 * WP-CLI helper for tools/baselines-themes.spec.ts (Task 18D) and tools/baselines-displays.spec.ts (Task DT1): sets
 * up, switches and undoes what the capture of Lasso's boxes and displays needs on the Local copy, through Lasso's own
 * code (Lasso 370 active). Developer tool, never shipped. Run as
 * `bash tools/local-wp.sh eval-file tools/lasso-capture.php <command> <json file>`.
 *
 * Commands (each reads its input from the JSON file and prints one JSON line):
 * - backup:   saves `lasso_settings` exactly (serialized, base64) and Lasso's active state into the file.
 * - setup:    makes the throwaway products, fields and one published post per group; prints their IDs.
 * - group:    makes one throwaway Lasso group of existing products, in the given order, and one published post whose
 *             content names it by `{GROUP}`; prints the group's term, slug and the post.
 * - display:  saves one group's Display settings over the backup (Lasso's `Setting::lasso_set_settings`).
 * - undo:     puts `lasso_settings` and Lasso's active state back, deletes what setup made.
 *
 * @package Affibloom
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.PHP.DiscouragedPHPFunctions, WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped -- a developer's WP-CLI tool on the Local copy.

use Lasso\Classes\Setting as Lasso_Setting;

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

list( $affibloom_command, $affibloom_file ) = array_pad( $args, 2, '' );
$affibloom_input                            = is_readable( $affibloom_file ) ? json_decode( (string) file_get_contents( $affibloom_file ), true ) : array();

/**
 * The Lasso settings with one group's changes on the Single buckets (the matrix's GroupSettings).
 *
 * @param array $saved    Staging's settings, as backed up.
 * @param array $settings The group's changes.
 * @return array
 */
function affibloom_capture_display( array $saved, array $settings ): array {
	$out = $saved;
	if ( ! empty( $settings['reset'] ) ) {
		// Lasso's "Reset display settings": every Display tab key at its default (settings-general/class-ajax.php).
		$defaults = unserialize( SETTINGS_DEFAULT );
		foreach ( Lasso_Setting::get_display_tab_setting_keys() as $key ) {
			if ( array_key_exists( $key, $defaults ) ) {
				$out[ $key ] = $defaults[ $key ];
			}
		}
		return $out;
	}
	if ( isset( $settings['elements'] ) ) {
		$out['display_elements']['single'] = $settings['elements'];
	}
	if ( isset( $settings['layout'] ) ) {
		$out['display_layout']['single'] = array_merge( $saved['display_layout']['single'] ?? array(), $settings['layout'] );
	}
	if ( isset( $settings['density'] ) ) {
		$out['display_density']['single'] = array(
			'preset' => $settings['density']['preset'],
			'values' => $settings['density']['values'] ?? ( $saved['display_density']['single']['values'] ?? array() ),
		);
	}
	if ( isset( $settings['keep_site_stripe_ui'] ) ) {
		$out['keep_site_stripe_ui'] = (bool) $settings['keep_site_stripe_ui'];
	}
	return $out;
}

global $wpdb;

const AFFIBLOOM_CAPTURE_QUIET = WPMU_PLUGIN_DIR . '/affibloom-capture-quiet.php';

switch ( $affibloom_command ) {
	case 'backup':
		$affibloom_state = array(
			'settings'     => base64_encode( serialize( get_option( 'lasso_settings' ) ) ),
			'lasso_active' => in_array( 'lasso/affiliate-plugin.php', (array) get_option( 'active_plugins' ), true ),
			'version'      => defined( 'LASSO_VERSION' ) ? LASSO_VERSION : '',
		);
		file_put_contents( $affibloom_file, wp_json_encode( $affibloom_state ) );
		// Local's php.ini has display_errors On, so a PHP warning Lasso raises prints inside the box (Lasso 370:
		// "Undefined property: stdClass::$show_field_name" for a rating set by the box's attribute). Readers never
		// see one: for the run, a must-use plugin turns the display off, as production has it; undo removes it.
		wp_mkdir_p( WPMU_PLUGIN_DIR );
		file_put_contents( AFFIBLOOM_CAPTURE_QUIET, "<?php\n// Affibloom Task 18D capture (tools/lasso-capture.php); removed when the run ends.\nini_set( 'display_errors', '0' );\n" );
		echo wp_json_encode( array( 'version' => $affibloom_state['version'] ) ), "\n";
		break;

	case 'setup':
		// Two products linked to pages of this site, through Lasso's own "Add a new link".
		$affibloom_made = array(
			'products' => array(),
			'fields'   => array(),
			'posts'    => array(),
		);
		$affibloom_link = new Lasso_Affiliate_Link();
		foreach ( array( 'fields', 'copy' ) as $affibloom_ref ) {
			$affibloom_made['products'][ $affibloom_ref ] = (int) $affibloom_link->lasso_add_a_new_link( home_url( '/?affibloom-capture=' . $affibloom_ref . '-' . time() ) );
		}
		$affibloom_db = new Lasso_DB();
		foreach ( $affibloom_input['fields'] as $affibloom_field ) {
			$affibloom_made['fields'][ $affibloom_field['type'] ] = (int) $affibloom_db->create_new_field( $affibloom_input['title'] . ' ' . $affibloom_field['type'], $affibloom_field['type'], '' );
		}
		// Field values, as the product page saves them (lasso_save_field_positions): lasso_id, field_id, value, order, visible.
		foreach ( $affibloom_input['values'] as $affibloom_ref => $affibloom_rows ) {
			foreach ( $affibloom_rows as $affibloom_order => $affibloom_row ) {
				$affibloom_field_id = isset( $affibloom_row['type'] ) ? $affibloom_made['fields'][ $affibloom_row['type'] ] : (int) $affibloom_row['id'];
				$wpdb->replace(
					$wpdb->prefix . LASSO_FIELD_MAPPING,
					array(
						'lasso_id'      => $affibloom_made['products'][ $affibloom_ref ],
						'field_id'      => $affibloom_field_id,
						'field_value'   => $affibloom_row['value'],
						'field_order'   => $affibloom_order,
						'field_visible' => 1,
					)
				);
			}
		}
		// One published post per group; its content names the products by placeholder.
		foreach ( $affibloom_input['posts'] as $affibloom_group => $affibloom_content ) {
			$affibloom_content                           = strtr(
				$affibloom_content,
				array(
					'{FIELDS}' => $affibloom_made['products']['fields'],
					'{COPY}'   => $affibloom_made['products']['copy'],
				)
			);
			$affibloom_made['posts'][ $affibloom_group ] = wp_insert_post(
				array(
					'post_type'    => 'post',
					'post_status'  => 'publish',
					'post_title'   => $affibloom_input['title'] . ' ' . $affibloom_group,
					'post_content' => $affibloom_content,
				)
			);
		}
		echo wp_json_encode( $affibloom_made ), "\n";
		break;

	case 'group':
		// Lasso's group: a `lasso-cat` term on the products and its order rows (the Groups screen's own two writes).
		$affibloom_slug = 'affibloom-capture-' . time();
		$affibloom_term = wp_insert_term( $affibloom_input['title'], LASSO_CATEGORY, array( 'slug' => $affibloom_slug ) );
		if ( is_wp_error( $affibloom_term ) ) {
			WP_CLI::error( $affibloom_term->get_error_message() );
		}
		foreach ( $affibloom_input['products'] as $affibloom_order => $affibloom_product ) {
			wp_set_object_terms( (int) $affibloom_product, array( (int) $affibloom_term['term_id'] ), LASSO_CATEGORY, true );
			$wpdb->insert(
				$wpdb->prefix . LASSO_CATEGORY_ORDER_DB,
				array(
					'item_id'     => (int) $affibloom_product,
					'parent_slug' => $affibloom_slug,
					'term_order'  => (int) $affibloom_order,
				)
			);
		}
		$affibloom_post = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $affibloom_input['title'],
				'post_content' => str_replace( '{GROUP}', $affibloom_slug, $affibloom_input['content'] ),
			)
		);
		echo wp_json_encode(
			array(
				'group' => array(
					'term' => (int) $affibloom_term['term_id'],
					'slug' => $affibloom_slug,
				),
				'posts' => array( 'displays' => $affibloom_post ),
			)
		), "\n";
		break;

	case 'display':
		$affibloom_saved = unserialize( base64_decode( $affibloom_input['backup']['settings'] ) );
		Lasso_Setting::lasso_set_settings( affibloom_capture_display( $affibloom_saved, $affibloom_input['settings'] ) );
		echo wp_json_encode( array( 'saved' => true ) ), "\n";
		break;

	case 'undo':
		$affibloom_backup = $affibloom_input['backup'];
		update_option( 'lasso_settings', unserialize( base64_decode( $affibloom_backup['settings'] ) ) );
		$affibloom_made = $affibloom_input['made'] ?? array();
		foreach ( (array) ( $affibloom_made['products'] ?? array() ) as $affibloom_product ) {
			$wpdb->delete( $wpdb->prefix . LASSO_FIELD_MAPPING, array( 'lasso_id' => (int) $affibloom_product ) );
			wp_delete_post( (int) $affibloom_product, true );
		}
		foreach ( (array) ( $affibloom_made['fields'] ?? array() ) as $affibloom_field ) {
			$wpdb->delete( $wpdb->prefix . LASSO_FIELDS, array( 'id' => (int) $affibloom_field ) );
		}
		foreach ( (array) ( $affibloom_made['posts'] ?? array() ) as $affibloom_post ) {
			wp_delete_post( (int) $affibloom_post, true );
		}
		if ( ! empty( $affibloom_made['group'] ) ) {
			$wpdb->delete( $wpdb->prefix . LASSO_CATEGORY_ORDER_DB, array( 'parent_slug' => $affibloom_made['group']['slug'] ) );
			wp_delete_term( (int) $affibloom_made['group']['term'], LASSO_CATEGORY );
		}
		if ( file_exists( AFFIBLOOM_CAPTURE_QUIET ) ) {
			unlink( AFFIBLOOM_CAPTURE_QUIET );
		}
		$affibloom_same = get_option( 'lasso_settings' ) == unserialize( base64_decode( $affibloom_backup['settings'] ) ); // phpcs:ignore Universal.Operators.StrictComparisons -- arrays compared by value.
		echo wp_json_encode( array( 'settings_restored' => $affibloom_same ) ), "\n";
		break;

	default:
		WP_CLI::error( 'Command: backup, setup, group, display or undo.' );
}
