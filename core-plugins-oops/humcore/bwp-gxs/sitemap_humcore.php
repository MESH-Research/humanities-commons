<?php

/*
 * Add support for HumCORE to the Better WordPress Google XML Sitemaps plugin
 * Deposits from all networks will be in the HC sitemap xml file 
 */

class BWP_GXS_MODULE_SITEMAP_HUMCORE extends BWP_GXS_MODULE {

	public function __construct() {
                $this->type = 'url';
		$this->perma_struct = get_option('permalink_structure');
	}

	protected function init_module_properties() {
		$this->post_type = get_post_type_object($this->requested);
	}

	/**
	 * This is the main function that generates our data.
	 *
	 * Since we are dealing with heavy queries here, it's better that you use
	 * generate_data() which will get called by build_data(). This way you will
	 * query for no more than the SQL limit configurable in this plugin's
	 * option page. If you happen to use LIMIT in your SQL statement for other
	 * reasons then use build_data() instead.
	 */
	protected function generate_data() {

		global $wpdb, $post;

		$data = array();
		$found_posts = false;

		$group_types = bp_groups_get_group_types();

		//Loop thru all societies
		foreach ( $group_types as $group_type ) {

			$root_blog_id = (int) constant( strtoupper( $group_type ) . '_ROOT_BLOG_ID' );
			$society_id =  $group_type;

			$switched = false;
			//Whitelist the societies processed for now.
			if ( ! empty( $root_blog_id ) && in_array( $group_type, array( 'ajs', 'aseees', 'hc', 'mla' ) ) ) {
				if ( $root_blog_id !== get_current_blog_id() ) {
					switch_to_blog( $root_blog_id );
					$switched = true;
				}

				$deposits_post_query = "
					SELECT ID, post_author, post_date,
						IF(post_date_gmt = '0000-00-00 00:00:00', post_date, post_date_gmt) AS post_date_gmt,
						post_content, post_title, post_excerpt, post_status, comment_status, ping_status,
						post_password, post_name, to_ping, pinged, post_modified ,
						IF(post_modified_gmt = '0000-00-00 00:00:00', post_modified, post_modified_gmt) AS post_modified_gmt,
						post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count
					FROM " . $wpdb->posts . "
					WHERE post_status = 'publish'
						AND post_type = 'humcore_deposit'
						AND post_parent = 0
					ORDER BY post_modified DESC";

				// Use $this->get_results instead of $wpdb->get_results.
				$deposits_posts = $this->get_results( $deposits_post_query );
				if ( ! isset( $deposits_posts ) || 0 == sizeof( $deposits_posts ) ) {
					continue;
				}

				for ( $i = 0; $i < sizeof( $deposits_posts ); $i++ ) {
					$post = $deposits_posts[$i];
					$post_metadata = json_decode( get_post_meta( $post->ID, '_deposit_metadata', true ), true );

					$data = $this->init_data( $data );
					// We cannot use the WP get_permalink function.
					$data['location'] = sprintf( '%1$s/deposits/item/%2$s/', HC_SITE_URL, $post_metadata['pid'] );
					$data['lastmod']  = $this->get_lastmod( $post );
					$data['freq']     = $this->cal_frequency( $post );
					$data['priority'] = $this->cal_priority( $post, $data['freq'] );
					$this->data[] = $data;
				}

			}
			unset( $deposits_posts );
			if ( $switched ) {
				restore_current_blog();
			}

		}
		return false; //We control when we're done. TODO handle more than 50,000 depoists.

	}
}
