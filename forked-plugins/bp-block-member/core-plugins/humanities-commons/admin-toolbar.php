<?php
/**
 * Humanities Commons admin toolbar customizations
 *
 * @package Xxxx
 * @subpackage Xxxx
 * @since x.x.x
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Remove unwanted entries.
 *
 * @since x.x.x
 */
function hcommons_admin_bar_render() {

        global $wp_admin_bar, $humanities_commons;

        $wp_admin_bar->remove_menu( 'wp-logo' );
        $wp_admin_bar->remove_menu( 'about' );
        $wp_admin_bar->remove_menu( 'documentation' );
        $wp_admin_bar->remove_menu( 'support-forums' );
        $wp_admin_bar->remove_menu( 'feedback' );
        $wp_admin_bar->remove_menu( 'updates' );

//        $wp_admin_bar->remove_menu( 'my-account-blogs' );
        $wp_admin_bar->remove_menu( 'my-account-forums' );
        $wp_admin_bar->remove_menu( 'my-account-groups' );
        $wp_admin_bar->remove_menu( 'my-account-notifications' );
//        $wp_admin_bar->remove_menu( 'my-account-xprofile' );

        $wp_admin_bar->remove_menu( 'my-account-buddydrive' );
//        $wp_admin_bar->remove_menu( 'my-account-buddydrive-files' );
//        $wp_admin_bar->remove_menu( 'my-account-buddydrive-members' );
        $wp_admin_bar->remove_menu( 'my-account-settings-profile' );

        if ( ! is_admin() ) {
                $site_name_clone = $wp_admin_bar->get_node( 'site-name' );
                $wp_admin_bar->remove_menu( 'site-name' );
                $wp_admin_bar->remove_menu( 'dashboard' );
		if ( is_object( $site_name_clone ) ) {
			$site_name_clone->href = str_replace( '/wp-admin', '', $site_name_clone->href );
			$wp_admin_bar->add_menu( $site_name_clone );
		}
        }

        if ( ! is_user_logged_in() ) {
                $wp_admin_bar->remove_menu( 'bp-login' );
		if ( hcommons_check_non_member_active_session() ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'bp-login',
				'parent' => false,
				'title' => __( strtoupper( get_network_option( '', 'society_id' ) ) . ' Visitor' ),
			) );
		} else {
			$wp_admin_bar->add_menu( array(
				'id' => 'bp-login',
				'parent' => false,
				'title' => __( 'Log in' ),
				'href' => wp_login_url(),
			) );
		}
        }

		// add user societies to adminbar for easy navigation to society sites from non-society sites
		$user_memberships = $humanities_commons::hcommons_get_user_memberships();

		if ( ! empty( $user_memberships ) && ! empty( $user_memberships['societies'] ) ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'hc-societies',
				'parent' => false,
				'title' => __( 'My Societies' )
			) );

			foreach ( $user_memberships['societies'] as $society_id ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'hc-society-' . $society_id,
					'parent' => 'hc-societies',
					'title' => strtoupper( $society_id ),
					'href' => sprintf(
						'https://%s%s',
						( 'hc' === $society_id ) ? '' : "$society_id.",
						$humanities_commons::$main_site->domain
					)
				) );
			}
		}

	if ( ! bp_is_root_blog() ) {
		$wp_admin_bar->remove_menu( 'bp-register' );
	}

	$nodes = $wp_admin_bar->get_nodes();
	//hcommons_write_error_log( 'info', '****ADMIN_BAR_RENDER****-'.var_export($nodes,true) );

	$vetted_user = humanities_commons::hcommons_vet_user();
	if ( ! $vetted_user ) {
		$wp_admin_bar->remove_menu( 'my-account-bp_docs' );
	}

}
add_action( 'wp_before_admin_bar_render', 'hcommons_admin_bar_render' );

function hcommons_admin_bar_enqueue_style() {
        wp_enqueue_style( 'humanities-commons', plugins_url( 'assets/css/main.css' , __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'hcommons_admin_bar_enqueue_style' );

function debug_admin_bar_init() {
        global $wp_admin_bar, $humanities_commons;
	//hcommons_write_error_log( 'info', '****ADMIN_BAR_INIT****-'.var_export($wp_admin_bar,true) );
}
add_action( 'admin_bar_init', 'debug_admin_bar_init' );

/* PHP Fatal error:  Uncaught Error: Call to undefined function wp_get_current_user() in /srv/www/commons/current/web/wp/wp-includes/capabilities.php:428:w
if ( ! current_user_can( 'manage_options' ) ) {
	add_filter( 'show_admin_bar', '__return_false' );
}
*/
