<?php
/**
 * BP_Component class defining the humcore_deposits component type.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * The humcore_deposits component type class.
 */
class Humcore_Deposit_Component extends BP_Component {
	/**
	 * Initial component setup.
	 */
	public function __construct() {

		global $bp;

		parent::start(
			// Unique component ID.
			'humcore_deposits',
			// Used by BP when listing components (eg in the Dashboard).
			__( 'Humanities CORE Deposits', 'humcore_domain' ),
			dirname( __FILE__ )
		);

		$this->includes();
		$this->setup_filters();
		bp_register_template_stack( 'humcore_register_template_location', 16 );
		$bp->active_components[ $this->id ] = '1';

	}

	/**
	 * Include component files.
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		parent::includes(
			array(
				'ajax-functions.php',
				'cssjs.php',
				'deposit.php',
				'deposit-edit.php',
				'deposit-functions.php',
				'functions.php',
				'screen-functions.php',
				'screens.php',
				'class-humcore-deposit-search-results.php',
				'template-functions.php',
			)
		);

	}

	/**
	 * Set up component data, as required by BP.
	 */
	public function setup_globals( $args = array() ) {

		parent::setup_globals(
			array(
				'slug'          => 'deposits', // Used for building URLs.
				'has_directory' => true,
				'search_string' => 'Search Deposits...',
				'root_slug'     => 'deposits',
			)
		);

	}

	/**
	 * Set up component navigation, and register display callbacks.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// Only grab count if we're on a user page.
		$count = 0;
		if ( bp_is_user() ) {
			$count = $this->humcore_get_user_deposit_count();
		}
		$class    = ( 0 === $count ) ? 'no-count' : 'count';
		$nav_name = sprintf(
			__( '%1$sCORE%2$s deposits <span class="%3$s">%4$s</span>', 'humcore_domain' ),
			'<em>', '</em>', esc_attr( $class ), number_format_i18n( $count )
		);
		$main_nav = array(
			'name'                => $nav_name,
			'slug'                => $this->slug,
			'position'            => 35,
			'default_subnav_slug' => 'my-deposits',
			'screen_function'     => array( $this, 'screen_function' ),
		);

		/** BuddyPress needs to have at least one subnav item, even if
		 * it's redundant
		 */
		$sub_nav[] = array(
			'name'        => __( 'Deposits', 'humcore_domain' ),
			'slug'        => 'my-deposits',
			'parent_slug' => 'deposits',
			'parent_url'  => bp_displayed_user_domain() . 'deposits/',
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up actions necessary for the component.
	 *
	 * @since BuddyPress (1.6)
	 */
	public function setup_actions() {

		add_action( 'humcore_deposits_results_deposit_sub_types', array( $this, 'humcore_deposits_results_deposit_sub_types' ) );
		add_action( 'bp_before_directory_deposits_content', array( $this, 'humcore_before_directory_deposits_content' ) );
		add_action( 'humcore_deposits_feed_item_content', 'humcore_deposits_feed_item_content' );
		add_action( 'humcore_deposits_list_entry_content', 'humcore_deposits_list_entry_content' );
		add_action( 'humcore_deposits_entry_content', 'humcore_deposits_entry_content' );
		add_action( 'humcore_deposit_item_content', 'humcore_deposit_item_content' );
		add_action( 'humcore_deposit_item_review_content', 'humcore_deposit_item_review_content' );
		add_action( 'humcore_deposit_item_edit_content', 'humcore_deposit_item_edit_content' );

		add_action( 'bp_activity_filter_options', array( $this, 'display_activity_actions' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'display_activity_actions' ) );
		add_action( 'bp_setup_nav', array( $this, 'humcore_setup_deposit_group_nav' ) );
		parent::setup_actions();
	}

	/**
	 * Set up filter necessary for the component.
	 */
	public function setup_filters() {

		add_filter( 'pre_get_document_title', array( $this, 'humcore_filter_item_wp_title' ) );
		add_filter( 'bp_located_template', array( $this, 'humcore_load_template_filter' ), 10, 2 );
		add_filter( 'bp_notifications_get_registered_components', array( $this, 'humcore_filter_notifications_get_registered_components' ) );
		/* add_filter( 'bp_dtheme_ajax_querystring', array( $this, 'humcore_override_ajax_querystring' ), 10, 7 ); */
	}

	/**
	 * Override the ajax querystring.
	 */
	public function humcore_override_ajax_querystring( $query_string, $object, $object_filter, $object_scope, $object_page, $object_search_terms, $object_extras ) {

		// TODO this overrides $my_querystring setting in themes/cbox-mla/activity/activity-loop.php - let's find a better way.
		if ( 'activity' == $object ) {
			// Let's just filter when we're on the activity page.
			if ( strpos( $query_string, 'type=' ) === false ) {
				/** If there's no type filter, then the type
				 * filter is really "everything." In that case,
				 * hijack it and make it not-so-everything
				 * (i.e. remove membership data)
				*/
				$my_querystring = 'type=activity_update,new_blog_post,new_blog_comment,created_group,updated_profile,new_forum_topic,new_forum_post,new_groupblog_post,added_group_document,edited_group_document,bp_doc_created,bp_doc_edited,bp_doc_comment,bbp_topic_create,bbp_reply_create,new_deposit,new_group_deposit&action=activity_update,new_blog_post,new_blog_comment,created_group,updated_profile,new_forum_topic,new_forum_post,new_groupblog_post,added_group_document,edited_group_document,bp_doc_created,bp_doc_edited,bp_doc_comment,bbp_topic_create,bbp_reply_create,new_deposit,new_group_deposit';
				if ( strlen( $query_string ) > 0 ) {
					$query_string = $my_querystring . '&' . $query_string;
				} else {
					$query_string = $my_querystring;
				}
			}
		}
		return $query_string;

	}

	/**
	 * Setup deposit group nav.
	 */
	public function humcore_setup_deposit_group_nav() {

		// Only display if we're on a certain type of group page.
		if ( bp_is_group() && ( 'hidden' !== bp_get_group_status( groups_get_group( array( 'group_id' => bp_get_current_group_id() ) ) ) || in_array( bp_get_current_group_id(), humcore_member_groups_with_authorship() ) ) ) {
			$count = $this->humcore_get_group_deposit_count();
			$class = ( 0 === $count ) ? 'no-count' : 'count';
			if ( 'public' === bp_get_group_status( groups_get_group( array( 'group_id' => bp_get_current_group_id() ) ) ) ) {
				$nav_name = sprintf(
					__( 'From %1$sCORE%2$s <span class="%3$s">%4$s</span>', 'humcore_domain' ),
					'<em>', '</em>', esc_attr( $class ), number_format_i18n( $count )
				);
			} else {
				$nav_name = sprintf(
					__( '%1$sCORE%2$s collection <span class="%3$s">%4$s</span>', 'humcore_domain' ),
					'<em>', '</em>', esc_attr( $class ), number_format_i18n( $count )
				);
			}

			bp_core_new_subnav_item(
				array(
					'name'            => $nav_name,
					'slug'            => 'deposits',
					'parent_url'      => bp_get_group_permalink( groups_get_current_group() ),
					'parent_slug'     => bp_get_current_group_slug(),
					'screen_function' => array( $this, 'humcore_group_deposits_screen_function' ),
					'position'        => 35,
				)
			);
		}
	}

	/**
	 * Get user deposit count.
	 */
	public function humcore_get_user_deposit_count() {

		$displayed_user = bp_get_displayed_user();
		humcore_has_deposits( sprintf( 'username=%s', urlencode( $displayed_user->userdata->user_login ) ) );
		return (int) humcore_get_deposit_count();

		/*
		Using solr counts now.
		$user_deposits = bp_activity_get( array(
			'filter' => array(
				'user_id' => bp_displayed_user_id(),
				'action' => 'new_deposit'
				)
			) );

		return (int) $user_deposits['total'];
		*/
	}

	/**
	 * Get group deposit count.
	 */
	public function humcore_get_group_deposit_count() {

		if ( in_array( bp_get_current_group_id(), humcore_member_groups_with_authorship() ) ) {
			humcore_has_deposits( sprintf( 'facets[author_facet][]=%s', urlencode( bp_get_current_group_name() ) ) );
		} else {
			humcore_has_deposits( sprintf( 'facets[group_facet][]=%s', urlencode( bp_get_current_group_name() ) ) );
		}
		return (int) humcore_get_deposit_count();

		/*
		Using solr counts now.
		$group_deposits = bp_activity_get( array(
			'filter' => array(
				'primary_id' => bp_get_current_group_id(),
				'action' => 'new_group_deposit'
				)
			) );

		return (int) $group_deposits['total'];
		*/
	}

	/**
	 * Group deposits screen logic.
	 */
	public function humcore_group_deposits_screen_function() {

		if ( ! humcore_check_internal_status() ) {
			add_action( 'bp_template_content', 'humcore_get_offline_content' );
		} else {
			add_action( 'bp_template_content', array( $this, 'humcore_group_deposits_list' ) );
		}
		bp_core_load_template( 'deposits/single/group-deposits' ); // Must use this here instead of bp_get_template_part.

	}

	/**
	 * Set up display screen logic.
	 */
	public function screen_function() {

		if ( ! humcore_check_internal_status() ) {
			add_action( 'bp_template_content', 'humcore_get_offline_content' );
		} else {
			add_action( 'bp_template_content', array( $this, 'humcore_user_deposits_list' ) );
		}
		bp_core_load_template( 'deposits/single/user-deposits' ); // Must use this here instead of bp_get_template_part.

	}

	/**
	 * Find templates in plugin when using bp_core_load_template.
	 */
	public function humcore_load_template_filter( $found_template, $templates ) {

		if ( ! bp_is_current_action( 'my-deposits' ) && ! bp_is_current_action( 'deposits' ) ) {
			return $found_template;
		}

		$filtered_template = '';
		foreach ( (array) $templates as $template ) {
			if ( file_exists( get_stylesheet_directory() . '/' . $template ) ) {
				$filtered_template = get_stylesheet_directory() . '/' . $template;
				break;
			} elseif ( file_exists( get_template_directory() . '/' . $template ) ) {
				$filtered_template = get_template_directory() . '/' . $template;
				break;
			} elseif ( file_exists( humcore_register_template_location() . $template ) ) {
				$filtered_template = humcore_register_template_location() . $template;
				break;
			}
		}

		return apply_filters( 'humcore_load_template_filter', $filtered_template );
	}

	/**
	 * User deposits loop screen logic.
	 */
	public function humcore_user_deposits_list() {

		bp_locate_template( array( 'deposits/user-deposits-loop.php' ), true );

	}

	/**
	 * Group deposits loop screen logic.
	 */
	public function humcore_group_deposits_list() {

		bp_locate_template( array( 'deposits/group-deposits-loop.php' ), true );

	}

	/**
	 * Returns new actions.
	 *
	 * @return array
	 */
	private function list_actions() {

		$bp_activity_actions      = buddypress()->activity->actions;
		$humcore_deposits_actions = array();

		if ( ! empty( $bp_activity_actions->humcore_deposits ) ) {
			$humcore_deposits_actions = array_values( (array) $bp_activity_actions->humcore_deposits );
		}

		return $humcore_deposits_actions;
	}

	/**
	 * Displays new actions into the Activity select boxes
	 * to filter activities
	 * - Activity Directory
	 * - Single and Member activity screens
	 *
	 * @return string html output
	 */
	public function display_activity_actions() {

		$humcore_deposits_actions = $this->list_actions();

		if ( empty( $humcore_deposits_actions ) ) {
			return;
		}

		foreach ( $humcore_deposits_actions as $type ) : ?>
			<option value="<?php echo esc_attr( $type['key'] ); ?>"><?php echo esc_attr( $type['value'] ); ?></option>
		<?php
		endforeach;
	}

	/**
	 * Show deposit button to logged in users.
	 */
	public function humcore_before_directory_deposits_content() {

		if ( is_user_logged_in() && humanities_commons::hcommons_vet_user() && humcore_is_deposit_directory() ) {
			echo '<a href="/deposits/item/new/" class="bp-deposits-deposit button" title="Upload Your Work">Upload Your Work</a>';
		}

		humcore_has_deposits( bp_ajax_querystring( 'deposits' ) );

	}

	/**
	 * Display search params.
	 */
	public function humcore_deposits_results_deposit_sub_types() {

		$extended_query_string = humcore_get_search_request_querystring();
		$query_args            = wp_parse_args( $extended_query_string );

		$facet_display_titles = humcore_get_facet_titles();
		?>
		<li id="results-limit-to" class="last filter">
		<span>Filter(s): 
		<?php
		if ( ! empty( $query_args['facets'] ) ) {
			foreach ( $query_args['facets'] as $selected_facet => $facet_values ) {
				echo trim( esc_html( $facet_display_titles[ $selected_facet ] ) ) . ': <strong>' . esc_html( stripslashes( implode( ', ', $facet_values ) ) ) . '</strong> '; // XSS OK.
			}
		}
		?>
		</span></li>
		<?php if ( ! empty( $query_args['s'] ) ) { ?>
		<li id="results-search-term" class="last filter">
		<span>Search Term: 
		<?php echo '<strong>' . trim( esc_html( $query_args['s'] ) ) . '</strong>'; // XSS OK. ?>
		</span></li>
		<?php
}
	}

	/**
	 * Create a unique title for a deposit item.
	 *
	 * @param string $title Default title text for current view.
	 * @return string Filtered title.
	 */
	public function humcore_filter_item_wp_title( $title ) {

		global $wp, $paged, $page;

		$sep = ' | ';

		if ( is_feed() ) {
			return $title;
		}

		if ( ! empty( $wp->query_vars['pagename'] ) ) {
			if ( 'deposits/item' == $wp->query_vars['pagename'] ) {
				$item_found = humcore_has_deposits( 'include=' . $wp->query_vars['deposits_item'] );
				if ( $item_found ) {
					humcore_the_deposit();
					$the_deposit = humcore_get_current_deposit();
					$title       = $the_deposit->title . " $sep " . $wp->query_vars['deposits_item'] . " $sep " . 'Humanities CORE';
				} else {
					$title = 'Deposit Item' . " $sep " . $wp->query_vars['deposits_item'] . " $sep ";
				}
				if ( in_array( $wp->query_vars['deposits_command'], array( 'edit', 'review' ) ) ) {
					$title .= $wp->query_vars['deposits_command'] . " $sep ";
				}
			}
		}

		$title = wptexturize( $title );
		$title = convert_chars( $title );
		$title = esc_html( $title );
		return $title;
	}

	public function humcore_filter_notifications_get_registered_components( $component_names = array() ) {

		if ( ! is_array( $component_names ) ) {
			$component_names = array();
		}
		$bp = buddypress();
		array_push( $component_names, $bp->humcore_deposits->id );
		return $component_names;
	}

}

/**
 * Bootstrap the component.
 */
function humcore_deposit_component_init() {

	buddypress()->humcore_deposits = new Humcore_Deposit_Component();

}
add_action( 'bp_loaded', 'humcore_deposit_component_init' );
