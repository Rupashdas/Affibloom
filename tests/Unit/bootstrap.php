<?php
/**
 * Unit test bootstrap: Composer only, no WordPress.
 *
 * @package Affibloom
 */

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// affibloom.php defines this before Plugin::boot(); the unit suite does not load affibloom.php.
define( 'AFFIBLOOM_VERSION', '0.0.0-test' );
