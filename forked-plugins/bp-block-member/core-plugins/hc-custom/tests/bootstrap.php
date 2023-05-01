<?php
/**
 * Bootstraps WordPress, BuddyPress, and your code so that you can run tests on
 * it. The function `_manually_load_our_code` is probably the only section you
 * need to edit when adapting this for new projects.
 *
 * @package HC_Suggestions
 */

// Get codebase versions.
$wp_version = ( getenv( 'WP_VERSION' ) ) ? getenv( 'WP_VERSION' ) : 'latest';
$bp_version = ( getenv( 'BP_VERSION' ) ) ? getenv( 'BP_VERSION' ) : 'latest';

// Get paths to codebase installed by install script.
$wp_root_dir  = "/tmp/wordpress/$wp_version/src/";
$wp_tests_dir = "/tmp/wordpress/$wp_version/tests/phpunit";
$bp_tests_dir = "/tmp/buddypress/$bp_version/tests/phpunit";

// Set required environment variables.
putenv( 'WP_ABSPATH=' . $wp_root_dir );
putenv( 'WP_TESTS_DIR=' . $wp_tests_dir );
putenv( 'BP_TESTS_DIR=' . $bp_tests_dir );

// Let code know we are running tests.
define( 'RUNNING_TESTS', true );

// Load WordPress.
require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Load WordPress, BuddyPress, testable plugin code, and mocks.
 */
function _manually_load_our_code() {

	// Load BuddyPress.
	require_once getenv( 'BP_TESTS_DIR' ) . '/includes/loader.php';

	// Load plugin classes.
	require_once dirname( __FILE__ ) . '/../hc-custom.php';

}
tests_add_filter( 'muplugins_loaded', '_manually_load_our_code' );

// Bootstrap tests.
require_once $wp_tests_dir . '/includes/bootstrap.php';
require_once $bp_tests_dir . '/includes/testcase.php';
