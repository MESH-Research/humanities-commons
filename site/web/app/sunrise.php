<?php
/**
 * Sunrise.php runs early in the WordPress loading process (before mu-plugins).
 *
 * It is being used here for two purposes:
 *
 * (1) Allow sites to have mapped domains, allowing them to operate with
 * multiple URLs. 
 *
 * (2) Establish a common cookie domain for sites accross the network so that
 * logins persist between networks and sites.
 *
 * If a site has a mapped domain, the cookie domain will be set according to
 * that mapping. Otherwise it will be set to the most general domain that
 * matches that site. For example, mla.hcommons.org and hcommons.org will both
 * have the cookie domain of hcommons.org, the domain of the HC netork, while
 * somesite.commons.msu.edu will have the cookie domain of commons.msu.edu, the
 * domain of the MSU network. If no matching domain is found, the cookie domain
 * will fall back to the domain defined in .env.
 *
 * Note: This file runs in the global context---it is not contained in a
 * function. All of these variables are global.
 */

if ( !defined( 'SUNRISE_LOADED' ) )
	define( 'SUNRISE_LOADED', 1 );

if ( defined( 'COOKIE_DOMAIN' ) ) {
	die( 'The constant "COOKIE_DOMAIN" is defined (probably in wp-config.php). Please remove or comment out that define() line.' );
}

$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
$dm_domain = array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER[ 'HTTP_HOST' ] : null;

$no_www = preg_replace( '|^www\.|', '', $dm_domain );
if ( $no_www != $dm_domain ) {
	$where = $wpdb->prepare( 'domain IN (%s,%s)', $dm_domain, $no_www );
} else {
	$where = $wpdb->prepare( 'domain = %s', $dm_domain );
}

$wpdb->suppress_errors();
$domain_mapping_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->dmtable} WHERE {$where} ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1" );
$wpdb->suppress_errors( false );

if ( $domain_mapping_id ) {
	$current_blog = $wpdb->get_row("SELECT * FROM {$wpdb->blogs} WHERE blog_id = '$domain_mapping_id' LIMIT 1");
	$current_blog->domain = $dm_domain;
	$current_blog->path = '/';
	$blog_id = $domain_mapping_id;
	$site_id = $current_blog->site_id;

	define( 'COOKIE_DOMAIN', $dm_domain );

	$current_site = $wpdb->get_row( "SELECT * from {$wpdb->site} WHERE id = '{$current_blog->site_id}' LIMIT 0,1" );
	$current_site->blog_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain='{$current_site->domain}' AND path='{$current_site->path}'" );
	if ( function_exists( 'get_site_option' ) ) {
		$current_site->site_name = get_site_option( 'site_name' );
	} elseif ( function_exists( 'get_current_site_name' ) ) {
		$current_site = get_current_site_name( $current_site );
	}

	define( 'DOMAIN_MAPPING', 1 );
} else {
	$networks = get_networks();
	$matched_domain = '';
	foreach ( $networks as $network ) {
		if ( strpos( $dm_domain, $network->domain ) !== false ) {
			$network_subdomain_count = count( explode( '.', $network->domain ) );
			$current_subdomain_count = count( explode( '.', $matched_domain ) );
			if ( ! $matched_domain || $network_subdomain_count < $current_subdomain_count ) {
				$matched_domain = $network->domain;
			}
		}
	}
	
	if ( $matched_domain ) {
		define( 'COOKIE_DOMAIN', $matched_domain );
	} else {
		define( 'COOKIE_DOMAIN', getenv( 'WP_DOMAIN' ) );
	}
}
