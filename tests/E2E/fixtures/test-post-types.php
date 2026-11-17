<?php
/**
 * Plugin Name: Affibloom E2E test post types
 * Description: Two public post types for F21 AC4 (Task 35D): one the test chooses in Custom Link Detection, one it
 * leaves out. tests/E2E/acceptance/F21.spec.ts copies it into wp-content/mu-plugins for one test and removes it after.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		register_post_type(
			'affibloom_e2e_chosen',
			array(
				'public' => true,
				'label'  => 'E2E chosen',
			)
		);
		register_post_type(
			'affibloom_e2e_other',
			array(
				'public' => true,
				'label'  => 'E2E other',
			)
		);
	}
);
