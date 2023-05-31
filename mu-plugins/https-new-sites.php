<?php

/*
Plugin Name: Make new sites in the network with https URLs
Description: Force new sites in a multisite network to use HTTPS as the scheme.
Plugin Author: Jan Dembowski

This probably should not be necessary and the scheme should be picked up
by WordPress. But I could not get my new sites to use https so here I am.

This plugin
1. hooks wpmu_new_blogs
   https://codex.wordpress.org/Plugin_API/Action_Reference/wpmu_new_blog
2. switches to the new blog id
3. obtains the home and siteurl options
4. replaces the ^http:/ in those strings with https:/
5. and updates the options for that new site

It's a horrible hack. It does not do any checking and if the new site is not setup
with a valid x.509 cert for TLS then the site will not load or it will
toss scary browser warnings to the user.

This should be copied into mu-plugins to use.

*/

add_action( 'wpmu_new_blog', 'mh_new_site_http', 10, 6 );

function mh_new_site_http( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

        switch_to_blog( $blog_id );

        $mh_old_home_url = trailingslashit( esc_url( get_option( 'home' ) ) );
        $mh_old_site_url = trailingslashit( esc_url( get_option( 'siteurl' ) ) );

        $mh_new_home_url = preg_replace( '/^http:/' , 'https:' , $mh_old_home_url );
        $mh_new_site_url = preg_replace( '/^http:/' , 'https:' , $mh_old_site_url );

        update_option( 'home', $mh_new_home_url );
        update_option( 'siteurl', $mh_new_site_url );

        restore_current_blog();

}

