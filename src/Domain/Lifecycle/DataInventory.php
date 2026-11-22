<?php
/**
 * Everything Affibloom stores, by kind (Task 38).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Lifecycle;

/**
 * Everything Affibloom stores, by kind (Task 38; F37 R14-R19, N1): the list the opt-in removal on uninstall deletes
 * (Infrastructure\WordPress\DataEraser). DataInventoryCoverageTest scans `src/` and fails for a stored name that is not
 * here, so the list stays true as features land. The migrator's own keys (the Lasso import, the ID map) are the
 * migrator's to remove (Phase M, LM10; deviation 146). Browser storage (localStorage) is the browser's and is left.
 */
final class DataInventory {

	/** Options (wp_options rows), including the locks kept as options (OptionLock). */
	public const OPTIONS = array(
		'affibloom_settings',
		'affibloom_display',
		'affibloom_fields',
		'affibloom_icons',
		'affibloom_ai_texts',
		'affibloom_db_version',
		'affibloom_version',
		'affibloom_activated_at',
		'affibloom_upgrade_lock',
		'affibloom_amazon_credentials',
		'affibloom_amazon_rate',
		'affibloom_amazon_refresh',
		'affibloom_amazon_bulk_sync',
		'affibloom_amazon_import_batch',
		'affibloom_amazon_import_lock',
		'affibloom_sitestripe_notice',
		'affibloom_bulk_add',
		'affibloom_link_check',
		'affibloom_link_check_lock',
		'affibloom_link_index_state',
		'affibloom_link_index_lock',
		'affibloom_auto_detect_lock',
		'affibloom_job_slot_1',
		'affibloom_job_slot_2',
		'affibloom_repair',
		'affibloom_setup',
	);

	/** Transients by full name (each also has its `_transient_timeout_` row). */
	public const TRANSIENTS = array(
		'affibloom_cron_health',
		'affibloom_link_issue_counts',
	);

	/** Transients named by a prefix and a key: Amazon's tokens and searches, the clicks' repeat guard. */
	public const TRANSIENT_PREFIXES = array(
		'affibloom_amazon_token_',
		'affibloom_amazon_search_',
		'affibloom_c_',
	);

	/** User meta. */
	public const USER_META = array(
		'affibloom_per_page',
		'affibloom_saved_views',
		'affibloom_bulk_sync_dismissed',
		'affibloom_amazon_import_dismissed',
	);

	/** Post types: every post of them, in every status (trash and retired included), with its meta. */
	public const POST_TYPES = array(
		'affibloom_product',
		'affibloom_table',
	);

	/** Taxonomies: every term, with its meta. */
	public const TAXONOMIES = array(
		'affibloom_group',
	);

	/** Custom tables, without the site's prefix. */
	public const TABLES = array(
		'affibloom_link_locations',
		'affibloom_link_issues',
		'affibloom_clicks',
		'affibloom_amazon_products',
		'affibloom_amazon_short_links',
		'affibloom_url_variants',
		'affibloom_detected_links',
	);

	/** WP-Cron hooks (every scheduled event of them). */
	public const CRON_HOOKS = array(
		'affibloom_daily',
		'affibloom_link_check_batch',
		'affibloom_link_check_product',
		'affibloom_amazon_refresh',
		'affibloom_amazon_refresh_pass',
		'affibloom_amazon_bulk_sync',
		'affibloom_amazon_import',
		'affibloom_genius_cleanup',
		'affibloom_link_index_start',
		'affibloom_link_index_batch',
		'affibloom_link_index_daily',
		'affibloom_bulk_add',
		'affibloom_auto_detect',
		'affibloom_auto_detect_daily',
	);

	/** Post meta on any post: the products' and tables' own, and any left on other posts (none is written by design). */
	public const POST_META_PREFIX = '_affibloom_';

	/** The log folder under uploads (TL3). */
	public const LOG_FOLDER = 'affibloom-logs';
}
