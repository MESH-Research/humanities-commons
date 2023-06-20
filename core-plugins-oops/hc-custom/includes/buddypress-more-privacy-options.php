<?php
/**
 * Plugin Name: BuddyPress / More Privacy Options patch
 * Description: Hook into some functions provided by BuddyPress to accommodate features added by More Privacy Options
 *
 * This file contains code copied from BP that doesn't pass phpcs, ignore it.
 * @codingStandardsIgnoreFile
 *
 * @package Hc_Custom
 */

/**
 * ripped from BP_Blogs_Blog::get(), so we can add a filter to handle MPO options:
 * if it becomes possible to manipulate the sql that function uses with a parameter or global, we should do that instead
 *
 * @param array $return_value what BP_Blogs_Blog::get() returned. will be entirely replaced by this filter
 * @param array $args the args originally passed to BP_Blogs_Blog::get(), so we can reconstruct the query
 */
function more_privacy_options_blogs_get( $return_value, $args ) {
	global $wpdb;
	/**
	 * one of these things is not like the others...
	 * all variables passed to BP_Blogs_Blog::get() match their names given in $args except "limit" - that's "per_page"
	 */

	extract( $args );
	$limit = $per_page;
	$bp_q = buddypress();

	//	Visible(1)
	//  No Search(0)
	//  Network Users Only(-1)
	//  Site Members Only(-2)
	//  Site Admins Only(-3)
	//  Super Admins - everything...
	$visibilities = array();

	$user_sql = ! empty( $user_id ) ? $wpdb->prepare( ' AND b.user_id = %d', $user_id ) : '';

	if ( is_user_logged_in() ) {

		$logged_in_user_id    = get_current_user_id();

		if ( is_super_admin() ) {
			$visibilities[] = "Super Admins";
		}

		$member_types             = bp_get_member_type( $logged_in_user_id, false );

		$search_member_type_array = array_combine( array_map( 'strtolower', $member_types ), $member_types );
		$network_id               = Humanities_Commons::$society_id;
		if ( ! empty( $search_member_type_array[ $network_id ] ) ) {
			$visibilities[] = "Network Users";
		}

		$blogs_user_belongs_to = get_blogs_of_user( $logged_in_user_id );
		$userblogs             = false;

		if ( ! empty( $blogs_user_belongs_to ) ) {
			$visibilities[] = "Site Members";
			$userblogs  = array();
			$adminblogs = array();
			foreach ( $blogs_user_belongs_to as $a ) {
				$userblogs[] = $a->userblog_id;
				if ( current_user_can_for_blog( $a->userblog_id, 'activate_plugins' ) ) {
					$adminblogs[] = $a->userblog_id;
				}
			}
		}
	}
	

	$visibility_array = array( 0 , 1 );
   	if ( in_array( 'Super Admins' , $visibilities ) ) {
		array_push( $visibility_array, -1, -2, -3 );
	} else {

	    foreach ( $visibilities as $visibility ) {
		
		switch ( $visibility ) {
		  case "Network Users":
                        array_push( $visibility_array, -1 );
                        break;
                  case "Site Members":
                        array_push( $visibility_array, -2 );
                        if ( ! empty( $adminblogs ) ) {
                                //Site Admins
                                $userblogs  = array_merge( $userblogs, $adminblogs );
                                array_push( $visibility_array, -3 );
                        }
                        break;
	    	}
	    }
	}

	if ( in_array( 'Site Members' , $visibilities ) ) {
	    $hidden_sql = 'AND (wb.public in ( ' . implode(",", $visibility_array)  . ' ) OR b.blog_id in (' . implode( ",", $userblogs ) . ')) ';
	} else {
	    $hidden_sql = 'AND (wb.public in ( ' . implode(",", $visibility_array) . ')) ';
	}

	$pag_sql = ( $limit && $page ) ? $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) ) : '';

	switch ( $type ) {
		case 'active':
		default:
			$order_sql = 'ORDER BY bm.meta_value DESC';
			break;
		case 'alphabetical':
			$order_sql = 'ORDER BY bm_name.meta_value ASC';
			break;
		case 'newest':
			$order_sql = 'ORDER BY wb.registered DESC';
			break;
		case 'random':
			$order_sql = 'ORDER BY RAND()';
			break;
	}

	$include_sql = '';
    $include_blog_ids = array_filter( wp_parse_id_list( $include_blog_ids ) );
    if ( ! empty( $include_blog_ids ) ) {
 		$blog_ids_sql = implode( ',', $include_blog_ids );
 		$include_sql  = " AND b.blog_id IN ({$blog_ids_sql})";
 	}
	$search_terms_sql = '';
	if ( ! empty( $search_terms ) ) {
		$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
		$search_terms_sql  = $wpdb->prepare( 'AND (bm_name.meta_value LIKE %s OR bm_description.meta_value LIKE %s)', $search_terms_like, $search_terms_like );
	}
	$site_sql = "AND wb.site_id = " . get_current_network_id();
	$sql      = "FROM
		  " . $bp_q->blogs->table_name . " b
		  LEFT JOIN " . $bp_q->blogs->table_name_blogmeta . " bm ON (b.blog_id = bm.blog_id)
		  LEFT JOIN " . $bp_q->blogs->table_name_blogmeta . " bm_name ON (b.blog_id = bm_name.blog_id)
		  LEFT JOIN " . $bp_q->blogs->table_name_blogmeta . " bm_description ON (b.blog_id = bm_description.blog_id)
		  LEFT JOIN " . $wpdb->base_prefix . "blogs wb ON (b.blog_id = wb.blog_id $hidden_sql)
		  LEFT JOIN " . $wpdb->users . " u ON (b.user_id = u.ID)
		WHERE
		  wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0  $site_sql
		  AND bm.meta_key = 'last_activity' AND bm_name.meta_key = 'name' AND bm_description.meta_key = 'description'
		  $search_terms_sql $user_sql $include_sql";

	$search_sql = "SELECT b.blog_id,
		        b.user_id as admin_user_id,
		        u.user_email as admin_user_email,
		        wb.domain,
		        wb.path,
		        bm.meta_value as last_activity,
		        bm_name.meta_value as name " . $sql . " GROUP BY b.blog_id $order_sql $pag_sql";

	$count_sql = "SELECT COUNT(DISTINCT b.blog_id)" . $sql;

	$paged_blogs = $wpdb->get_results( $search_sql );
	$total_blogs = $wpdb->get_var( $count_sql );

	$blog_ids = array();
	foreach ( (array) $paged_blogs as $blog ) {
		$blog_ids[] = (int) $blog->blog_id;
	}

	$paged_blogs = BP_Blogs_Blog::get_blog_extras( $paged_blogs, $blog_ids, $type );

	if ( $update_meta_cache ) {
		bp_blogs_update_meta_cache( $blog_ids );
	}

	return array(
		'blogs' => $paged_blogs,
		'total' => $total_blogs,
	);

}

add_filter( 'bp_blogs_get_blogs', 'more_privacy_options_blogs_get', null, 3 );
