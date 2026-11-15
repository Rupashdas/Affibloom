<?php
/**
 * Saves the Local site's state before a Playwright run and puts it back after it (deviation 111), so a run needs no
 * backup, no reset and no clean-up by hand, and a failing test cannot leave its data behind.
 *
 * Usage:
 *     wp eval-file run-state.php save      # globalSetup (tests/E2E/run-state.ts)
 *     wp eval-file run-state.php restore   # globalTeardown (tests/E2E/run-state-teardown.ts)
 *
 * What is put back:
 * - every post (any type: products, posts, pages, revisions, attachments) and every term made during the run,
 *   found by ID: anything above the highest ID saved is the run's, and is deleted for good;
 * - every option whose name starts with `affibloom` (settings, Amazon credentials, jobs, test stubs): the saved rows
 *   are written back as they were, byte for byte, and options the run added are deleted; except the schema's
 *   `affibloom_db_version` and `affibloom_upgrade_lock`, which follow the code and its tables, not the run (C1C);
 * - the rows of Affibloom's own tables (`{prefix}affibloom_*`): each table's saved rows are written back and the
 *   run's deleted; a table the run's upgrade created keeps its definition and loses its rows;
 * - the status and slug of every product that existed (a test that trashes or restores one);
 * - the active plugins (a test that switches Lasso or Affibloom on or off).
 * Not put back: edits to the meta or content of posts that existed before the run, users, comments, and the schema
 * (a table or column the run's upgrade added stays, as the code expects it).
 *
 * The state is kept in `.local/e2e-run-state.json` until the restore. If a run was killed before its teardown, the
 * next `save` finds the file and restores it first, so the state saved is always the one from before any run.
 *
 * @package Affibloom
 */

defined( 'WP_CLI' ) || exit;

// deactivate_plugins() and activate_plugin() live in the admin includes, which WP-CLI does not load for eval-file.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$affibloom_state_file = dirname( __DIR__, 3 ) . '/.local/e2e-run-state.json';
$affibloom_action     = (string) ( $args[0] ?? '' );

/**
 * Reads the saved state file, refusing one that is not whole: a missing max_post_id would read as 0, and the restore
 * would then delete every post on the site.
 *
 * @param string $file The state file.
 * @return array<string, mixed>
 */
function affibloom_e2e_load_state( string $file ): array {
	$state = json_decode( (string) file_get_contents( $file ), true );
	$whole = is_array( $state )
		&& is_int( $state['max_post_id'] ?? null ) && $state['max_post_id'] > 0
		&& is_int( $state['max_term_id'] ?? null )
		&& is_array( $state['options'] ?? null ) && is_array( $state['products'] ?? null ) && is_array( $state['plugins'] ?? null );
	if ( ! $whole ) {
		WP_CLI::error( "run-state: {$file} is not a whole saved state; nothing was changed. Look at it, then delete it." );
	}
	return $state;
}

/**
 * Reads the site's state as it is now.
 *
 * @return array<string, mixed>
 */
function affibloom_e2e_read_state(): array {
	global $wpdb;
	$options = array_values(
		array_filter(
			$wpdb->get_results(
				$wpdb->prepare( "SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE %s", 'affibloom%' ),
				ARRAY_A
			),
			static fn( array $row ): bool => ! affibloom_e2e_is_schema_option( $row['option_name'] )
		)
	);
	$tables = array();
	foreach ( affibloom_e2e_tables() as $table ) {
		$tables[ $table ] = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $table ), ARRAY_A );
	}
	$products = $wpdb->get_results(
		$wpdb->prepare( "SELECT ID, post_status, post_name FROM {$wpdb->posts} WHERE post_type = %s", 'affibloom_product' ),
		ARRAY_A
	);
	return array(
		'max_post_id' => (int) $wpdb->get_var( "SELECT COALESCE(MAX(ID), 0) FROM {$wpdb->posts}" ),
		'max_term_id' => (int) $wpdb->get_var( "SELECT COALESCE(MAX(term_id), 0) FROM {$wpdb->terms}" ),
		'options'     => $options,
		'products'    => $products,
		'tables'      => $tables,
		'plugins'     => array_values( (array) get_option( 'active_plugins', array() ) ),
	);
}

/**
 * Whether an option is the schema's: it follows the code and its tables, so a run neither saves nor restores it.
 *
 * @param string $name An option name.
 */
function affibloom_e2e_is_schema_option( string $name ): bool {
	return in_array( $name, array( 'affibloom_db_version', 'affibloom_upgrade_lock' ), true );
}

/**
 * Affibloom's own tables on this site (`{prefix}affibloom_*`).
 *
 * @return list<string>
 */
function affibloom_e2e_tables(): array {
	global $wpdb;
	return array_values( (array) $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'affibloom_' ) . '%' ) ) );
}

/**
 * Puts the saved state back and prints what it changed.
 *
 * @param array<string, mixed> $state What affibloom_e2e_read_state() returned before the run.
 */
function affibloom_e2e_restore_state( array $state ): void {
	global $wpdb;

	// Posts made during the run, newest first so children go before their parents.
	$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID > %d ORDER BY ID DESC", $state['max_post_id'] ) );
	foreach ( $post_ids as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}

	// Terms made during the run. wp_delete_term() needs the taxonomy registered, which it is under WP-CLI.
	$terms     = $wpdb->get_results( $wpdb->prepare( "SELECT term_id, taxonomy FROM {$wpdb->term_taxonomy} WHERE term_id > %d", $state['max_term_id'] ), ARRAY_A );
	$not_known = array();
	foreach ( $terms as $term ) {
		$deleted = wp_delete_term( (int) $term['term_id'], $term['taxonomy'] );
		if ( true !== $deleted ) {
			$not_known[] = $term['taxonomy'] . ' ' . $term['term_id'];
		}
	}

	// Options: written back as raw rows, past update_option(), so no sanitize hook can change a saved value.
	$saved = array();
	foreach ( $state['options'] as $row ) {
		$saved[ $row['option_name'] ] = $row;
	}
	$now           = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'affibloom%' ) );
	$options_added = array_values( array_filter( array_diff( $now, array_keys( $saved ) ), static fn( string $name ): bool => ! affibloom_e2e_is_schema_option( $name ) ) );
	foreach ( $options_added as $name ) {
		$wpdb->delete( $wpdb->options, array( 'option_name' => $name ) );
	}
	foreach ( $saved as $row ) {
		$wpdb->replace( $wpdb->options, $row );
	}
	wp_cache_flush();

	// Affibloom's own tables: emptied, then the saved rows written back (a table new in this run has none saved).
	$rows_now = 0;
	foreach ( affibloom_e2e_tables() as $table ) {
		$rows_now += (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) );
		foreach ( (array) ( $state['tables'][ $table ] ?? array() ) as $row ) {
			$wpdb->insert( $table, $row );
		}
	}
	$rows_saved = array_sum( array_map( 'count', (array) ( $state['tables'] ?? array() ) ) );

	// Products that existed: status and slug back (wp_trash_post() renames the slug to "…__trashed").
	$products_changed = 0;
	foreach ( $state['products'] as $product ) {
		$post = get_post( (int) $product['ID'] );
		if ( ! $post || ( $post->post_status === $product['post_status'] && $post->post_name === $product['post_name'] ) ) {
			continue;
		}
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_status' => $product['post_status'],
				'post_name'   => $product['post_name'],
			),
			array( 'ID' => $post->ID )
		);
		if ( 'trash' !== $product['post_status'] ) {
			delete_post_meta( $post->ID, '_wp_trash_meta_status' );
			delete_post_meta( $post->ID, '_wp_trash_meta_time' );
		}
		clean_post_cache( $post->ID );
		++$products_changed;
	}

	// Plugins: the ones the run switched on are switched off, and the other way round, with their hooks.
	$active     = (array) get_option( 'active_plugins', array() );
	$switch_off = array_values( array_diff( $active, $state['plugins'] ) );
	$switch_on  = array_values( array_diff( $state['plugins'], $active ) );
	if ( $switch_off ) {
		deactivate_plugins( $switch_off, true );
	}
	foreach ( $switch_on as $plugin ) {
		activate_plugin( $plugin, '', false, true );
	}

	WP_CLI::log(
		sprintf(
			'run-state: deleted %d posts and %d terms, restored %d options (%d added by the run deleted), %d table rows (%d found), %d products, plugins off: %s, on: %s.',
			count( $post_ids ),
			count( $terms ) - count( $not_known ),
			count( $saved ),
			count( $options_added ),
			$rows_saved,
			$rows_now,
			$products_changed,
			$switch_off ? implode( ', ', $switch_off ) : 'none',
			$switch_on ? implode( ', ', $switch_on ) : 'none'
		)
	);
	if ( $not_known ) {
		WP_CLI::warning( 'run-state: terms not deleted (taxonomy not registered): ' . implode( ', ', $not_known ) );
	}
}

if ( 'save' === $affibloom_action ) {
	if ( file_exists( $affibloom_state_file ) ) {
		// A leftover from hours ago would delete everything made on the site since then; that is a person's call.
		if ( time() - (int) filemtime( $affibloom_state_file ) > 6 * HOUR_IN_SECONDS ) {
			WP_CLI::error( 'run-state: .local/e2e-run-state.json is from an unfinished run over 6 hours ago. Restore it (`wp eval-file tests/E2E/fixtures/run-state.php restore`) or delete it, then run again.' );
		}
		WP_CLI::warning( 'run-state: the last run ended before its teardown; putting its saved state back first.' );
		affibloom_e2e_restore_state( affibloom_e2e_load_state( $affibloom_state_file ) );
	}
	if ( ! is_dir( dirname( $affibloom_state_file ) ) ) {
		mkdir( dirname( $affibloom_state_file ), 0700, true );
	}
	$affibloom_json = wp_json_encode( affibloom_e2e_read_state() );
	if ( false === $affibloom_json || false === file_put_contents( $affibloom_state_file, $affibloom_json ) ) {
		WP_CLI::error( 'run-state: could not write .local/e2e-run-state.json.' );
	}
	WP_CLI::log( 'run-state: saved.' );
} elseif ( 'restore' === $affibloom_action ) {
	if ( ! file_exists( $affibloom_state_file ) ) {
		WP_CLI::error( 'run-state: nothing saved (no .local/e2e-run-state.json).' );
	}
	affibloom_e2e_restore_state( affibloom_e2e_load_state( $affibloom_state_file ) );
	wp_delete_file( $affibloom_state_file );
} else {
	WP_CLI::error( 'run-state: say "save" or "restore".' );
}
