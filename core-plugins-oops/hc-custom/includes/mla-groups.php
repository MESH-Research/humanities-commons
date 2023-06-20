<?php
/**
 * Legacy.
 * @codingStandardsIgnoreFile
 */

class MLA_Groups {

	function __construct() {
		add_action( 'bp_groups_directory_group_types', [ $this, 'add_type_filter' ] );
		add_action( 'bp_groups_directory_group_types', [ $this, 'add_status_filter' ] );

		add_action( 'wp_footer', [ $this, 'type_filter_js' ] );
		add_action( 'wp_footer', [ $this, 'status_filter_js' ] );

		add_filter( 'bp_before_has_groups_parse_args', [ $this, 'filter_bp_before_has_groups_parse_args' ] );
		add_filter( 'bp_groups_get_paged_groups_sql', [ $this, 'filter_bp_groups_get_paged_groups_sql' ], null, 3 );
	}

	function add_status_filter() {
		$str  = '<span class="filter-status">';
		$str .= '<select id="groups-filter-by-status">';
		$str .= '<option value="all">All Visibilities</option>';
		$str .= '<option value="public">Public</option>';
		$str .= '<option value="private">Private</option>';
		if ( is_admin() || is_super_admin() ) {
			$str .= '<option value="hidden">Hidden</option>';
		}
		$str .= '</select></span>';
		echo $str;
	}

	function add_type_filter() {
		$str  = '<span class="filter-type">';
		$str .= '<select id="groups-filter-by-type">';
		$str .= '<option value="all">All Types</option>';
		$str .= '<option value="committees">Committees</option>';
		$str .= '<option value="forums">Forums</option>';
		$str .= '<option value="prospective_forums">Prospective Forums</option>';
		$str .= '<option value="other">Other</option>';
		$str .= '</select></span>';
		echo $str;
	}

	function status_filter_js() {
		if ( wp_script_is( 'jquery', 'done' ) ) { ?>
			<script type="text/javascript">
				if (jq.cookie('bp-groups-status')) {
					jq('.filter-status select').val(jq.cookie('bp-groups-status'));
			}
			jq('.filter-status select').change( function() {

				if ( jq('.item-list-tabs li.selected').length )
					var el = jq('.item-list-tabs li.selected');
				else
					var el = jq(this);

				var css_id = el.attr('id').split('-');
				var object = css_id[0];
				var scope = css_id[1];
				var status = jq(this).val();
				var filter = jq('select#groups-order-by').val();
				var search_terms = '';

				jq.cookie('bp-groups-status',status,{ path: '/' });

				if ( jq('.dir-search input').length )
					search_terms = jq('.dir-search input').val();

				bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, jq.cookie('bp-' + object + '-extras') );

				return false;

			});
			</script>
		<?php
		}
	}

	function type_filter_js() {
		if ( wp_script_is( 'jquery', 'done' ) ) {
		?>
		<script>
			if (jq.cookie('bp-groups-status')) {
				jq('.filter-type select').val(jq.cookie('bp-groups-type'));
			}
			jq('.filter-type select').change( function() {

				if ( jq('.item-list-tabs li.selected').length )
					var el = jq('.item-list-tabs li.selected');
				else
					var el = jq(this);

				var css_id = el.attr('id').split('-');
				var object = css_id[0];
				var scope = css_id[1];
				var status = jq(this).val();
				var filter = jq('select#groups-order-by-type').val();
				var search_terms = '';

				jq.cookie('bp-groups-type',status,{ path: '/' });

				if ( jq('.dir-search input').length )
					search_terms = jq('.dir-search input').val();

				bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, jq.cookie('bp-' + object + '-extras') );

				return false;

			});
		</script>
		<?php
		}
	}

	function filter_bp_before_has_groups_parse_args( $args ) {
		$type = ( isset( $_COOKIE['bp-groups-type'] ) ) ? $_COOKIE['bp-groups-type'] : false;

		if ( bp_is_groups_directory() && ! empty( $type ) ) {
			switch ( $type ) {
				case 'committees':
					$value = '^M';
					break;
				case 'forums':
					$value = '^(D|G)';
					break;
				case 'prospective_forums':
					$value = '^F';
					break;
				case 'other':
					$value = '^U';
					break;
			}
		} elseif ( bp_is_user() && false !== strpos( $_SERVER['REQUEST_URI'], 'invite-anyone' ) ) {
			$value = '^U'; // exclude committees on member invite-anyone
		}

		if ( ! empty( $value ) ) {
			if ( empty( $args['meta_query'] ) ) {
				$args['meta_query'] = [];
			}

			$args['meta_query'][] = [
				'key'     => 'mla_oid',
				'value'   => $value,
				'compare' => 'RLIKE',
			];
		}

		return $args;
	}

	function filter_bp_groups_get_paged_groups_sql( $sql_str, $sql_arr, $r ) {
		$status = $_COOKIE['bp-groups-status'];

		if ( bp_is_groups_directory() && ! empty( $status ) ) {
			switch ( $status ) {
				case 'private':
				case 'public':
					$value = $status;
					break;
				case 'hidden':
					if ( is_admin() || is_super_admin() ) {
						$value = $status;
					}
					break;
			}

			if ( ! empty( $value ) ) {
				$sql_arr['where'] = ( ( isset( $sql_arr['where'] ) ) ? $sql_arr['where'] . ' AND ' : '' ) . "g.status = '$value'";
				$sql_str          = "{$sql_arr['select']} FROM {$sql_arr['from']} WHERE {$sql_arr['where']} {$sql_arr['orderby']} {$sql_arr['pagination']}";
			}
		}

		return $sql_str;
	}

}

function hcommons_init_mla_groups() {
	if ( class_exists( 'Humanities_Commons' ) && 'mla' === Humanities_Commons::$society_id ) {
		$MLA_Groups = new MLA_Groups();
	}
}
add_action( 'bp_init', 'hcommons_init_mla_groups' );

function mla_filter_gettext( $translated, $original, $domain ) {
	// This is an array of original strings
	// and what they should be replaced with
	$strings = array(
		'Username'    => 'User name', // per MLA house style
		'login'       => 'log-in', // per MLA house style
		'Group Blog'  => 'Site', // bp-groupblog textdomain fix
		'Blogs'       => 'Sites', // bp-groupblog textdomain fix
		'Blog'        => 'Site', // bp-groupblog textdomain fix
		'Friends'     => 'Contacts', // it's a formality thing
		'Friend'      => 'Contact',
		'Friendships' => 'Contacts',
		'Howdy'       => 'Hello',
			// Add some more strings here
	);

		// See if the current string is in the $strings array
		// If so, replace it's translation
	if ( ! empty( $strings[ $original ] ) ) {
		// This accomplishes the same thing as __()
		// but without running it through the filter again
		$translations = get_translations_for_domain( $domain );
		$translated   = $translations->translate( $strings[ $original ] );
	}

		return $translated;
}
add_filter( 'gettext', 'mla_filter_gettext', 10, 3 );

function mla_is_group_committee( $group_id = 0 ) {
	// use the current group if we're not passed one.
	if ( 0 == $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	// 2018-2022 MLA Conventions are a committee according to the OID but are not in fact a committee.
	if ( in_array( $group_id, array( 1001221, 1003090, 1003475, 1003732, 1003921 ) ) ) {
		return false;
	}

	// if mla_oid starts with "M," it's a committee
	return ( 'M' == substr( groups_get_groupmeta( $group_id, 'mla_oid' ), 0, 1 ) ) ? true : false;
}

function hcommons_filter_bp_get_group_type( $type, $group ) {
	if ( mla_is_group_committee( $group->id ) ) {
		$type = 'Committee';
	}

	return $type;
}
add_filter( 'bp_get_group_type', 'hcommons_filter_bp_get_group_type', null, 2 );