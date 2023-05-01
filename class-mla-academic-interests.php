<?php
/**
 * Main class
 *
 * @package Mla_Academic_Interests
 */

/**
 * Main class
 */
class Mla_Academic_Interests {


	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'register_mla_academic_interests_taxonomy' ) );
		add_action( 'wpmn_register_taxonomies', array( $this, 'register_mla_academic_interests_taxonomy' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'mla_academic_interests_cssjs' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'mla_academic_interests_cssjs' ) );

			$current_network = get_current_site();
		if ( 1 === (int) $current_network->id ) {
			add_action( 'admin_menu', array( $this, 'mla_academic_interests_admin_page' ) );
			add_filter( 'parent_file', array( $this, 'mla_academic_interests_fix_menu_highlight' ) );
			add_filter( 'manage_edit-mla_academic_interests_columns', array( $this, 'manage_mla_academic_interests_user_column' ) );
			add_filter( 'manage_mla_academic_interests_custom_column', array( $this, 'manage_mla_interests_columns' ), 10, 3 );
		}

		add_action( 'show_user_profile', array( $this, 'edit_user_mla_academic_interests_section' ) );
		add_action( 'edit_user_profile', array( $this, 'edit_user_mla_academic_interests_section' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_mla_academic_interests_terms' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_mla_academic_interests_terms' ) );

	}

	/**
	 * Register academic interests taxonomy.
	 */
	public function register_mla_academic_interests_taxonomy() {

		// Add new taxonomy, NOT hierarchical (like tags).
		$labels = array(
			'name'                => _x( 'Interests', 'taxonomy general name' ),
			'singular_name'            => _x( 'Interest', 'taxonomy singular name' ),
			'search_items'            => __( 'Search Interests' ),
			'popular_items'            => __( 'Popular Interests' ),
			'all_items'            => __( 'All Interests' ),
			'parent_item'            => null,
			'parent_item_colon'        => null,
			'edit_item'            => __( 'Edit Interest' ),
			'update_item'            => __( 'Update Interest' ),
			'add_new_item'            => __( 'Add New Interest' ),
			'new_item_name'            => __( 'New Interest Name' ),
			'separate_items_with_commas'    => __( 'Separate interests with commas' ),
			'add_or_remove_items'        => __( 'Add or remove interests' ),
			'choose_from_most_used'        => __( 'Choose from the most used interests' ),
			'not_found'            => __( 'No interests found.' ),
			'menu_name'            => __( 'Interests' ),
		);

		$args = array(
			'public'            => false,
			'hierarchical'            => false,
			'labels'            => $labels,
			'show_ui'            => true,
			'show_in_nav_menus'        => false,
			'show_admin_column'        => false,
			'update_count_callback'        => '_update_generic_term_count',
			'query_var'            => 'academic_interests',
			'rewrite'            => false,
		);

		register_taxonomy( 'mla_academic_interests', array( 'user' ), $args );
		register_taxonomy_for_object_type( 'mla_academic_interests', 'user' );

	}

	/**
	 * Register the plugin css and js files.
	 */
	public function mla_academic_interests_cssjs() {

		wp_register_script( 'select2_js', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js', array( 'jquery' ), '022316', true );
		wp_enqueue_script( 'select2_js' );

		wp_register_style( 'select2_css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css', '', '022316' );
		wp_enqueue_style( 'select2_css' );

		wp_register_script( 'mla_academic_interests_select2_js', plugin_dir_url( __FILE__ ) . 'assets/js/select2_init.js', array( 'jquery' ), '022316', true );
		wp_enqueue_script( 'mla_academic_interests_select2_js' );

		wp_register_style( 'mla_academic_interests_select2_css', plugin_dir_url( __FILE__ ) . 'assets/css/select2_init.css', '', '120415-1' );
		wp_enqueue_style( 'mla_academic_interests_select2_css' );
	}

	/**
	 * Return the combined academic interest subject and tag list.
	 *
	 * @return array
	 */
	public function mla_academic_interests_list() {

		$interests_list = array();

		$interest_terms = wpmn_get_terms(
			'mla_academic_interests',
			array(
				'orderby' => 'name',
				'fields' => 'all',
				'hide_empty' => 0,
			)
		);
		foreach ( $interest_terms as $term ) {
			$interests_list[ $term->name ] = $term->name;
		}

		natcasesort( $interests_list );

		return apply_filters( 'mla_academic_interests_list', $interests_list );

	}

	/**
	 * Create the admin page for the mla_academic_interests taxonomy under the Users menu.
	 */
	public function mla_academic_interests_admin_page() {

		$tax = get_taxonomy( 'mla_academic_interests' );

		add_users_page(
			esc_attr( $tax->labels->menu_name ),
			esc_attr( $tax->labels->menu_name ),
			$tax->cap->manage_terms,
			'edit-tags.php?taxonomy=' . $tax->name
		);
	}

	/**
	 * Fix taxonomy page so it highlights users instead of posts.
	 *
	 * @param string $parent_file passed by apply_filters().
	 * @return string
	 */
	public function mla_academic_interests_fix_menu_highlight( string $parent_file = '' ) {

		global $pagenow;

		if ( ! empty( $_GET['taxonomy'] ) && 'mla_academic_interests' === $_GET['taxonomy'] && 'edit-tags.php' === $pagenow ) {
			$parent_file = 'users.php';
		}
		return $parent_file;
	}

	/**
	 * Unsets the posts column and adds a users column on the manage mla_academic_interests admin page.
	 *
	 * @param array $columns An array of columns to be shown in the manage terms table.
	 *
	 * @return array
	 */
	public function manage_mla_academic_interests_user_column( $columns ) {

		unset( $columns['posts'] );
		$columns['users'] = __( 'Users' );
		return $columns;
	}

	/**
	 * Create custom column value(s) for the manage mla_academic_interests page.
	 *
	 * @param string     $out passed by apply_filters().
	 * @param string     $column_name passed by apply_filters().
	 * @param int|string $term_id passed by apply_filters().
	 * @return string
	 */
	public function manage_mla_interests_columns( string $out, string $column_name, $term_id ) {

		switch ( $column_name ) {
			case 'users':
				$term = wpmn_get_term( $term_id, 'mla_academic_interests' );
				$out .= $term->count;
				break;
			default:
				break;
		}

		return $out;

	}

	/**
	 * Adds an additional settings section on the edit user/profile page in the admin.
	 *
	 * This section allows users to select interests from the mla_academic_interests taxonomy.
	 *
	 * @param object $user The user object currently being edited.
	 */
	public function edit_user_mla_academic_interests_section( $user ) {

		$tax = get_taxonomy( 'mla_academic_interests' );

		/* Get the terms of the 'mla_academic_interests' taxonomy. */
		$terms = wpmn_get_terms(
			'mla_academic_interests', array(
				'hide_empty' => false,
			)
		); ?>

	 <h3><?php _e( 'Academic Interests' ); ?></h3>

	 <table class="form-table">

	  <tr>
	   <th><label for="academic-interests"><?php _e( 'Selected Interests' ); ?></label></th>

	   <td>
		<?php

				$html = '<span class="description">Enter interests from the existing list, or add new interests if needed.</span><br />';
				$html .= '<select name="academic-interests[]" class="js-basic-multiple-academic-interests-tags interests" multiple="multiple" data-placeholder="Enter interests.">';
				$interest_list = $this->mla_academic_interests_list();
				$input_interest_list = wpmn_get_object_terms(
					$user->ID, 'mla_academic_interests', array(
						'fields' => 'names',
					)
				);

				// Only add selected interests, load the rest over ajax.
		foreach ( $interest_list as $interest_key => $interest_value ) {
			if ( in_array( $interest_key, $input_interest_list ) ) {
				$html .= sprintf(
					'			<option class="level-1" selected="selected" value="%1$s">%2$s</option>' . "\n",
					$interest_key,
					$interest_value
				);
			}
		}

				$html .= '</select>';
				echo $html;

				?>
				</td>
	  </tr>

	 </table>
	<?php
	}

	/**
	 * Saves the terms selected on the edit user/profile page in the admin.
	 *
	 * @param int $user_id The ID of the user to save the terms for.
	 */
	public function save_user_mla_academic_interests_terms( $user_id ) {

		$tax = get_taxonomy( 'mla_academic_interests' );

		/* Make sure the current user can edit the user and assign terms before proceeding. */
		if ( ! current_user_can( 'edit_user', $user_id ) && current_user_can( $tax->cap->assign_terms ) ) {
			return false;
		}

		// If array add any new keywords.
		if ( is_array( $_POST['academic-interests'] ) ) {
			foreach ( $_POST['academic-interests'] as $term_id ) {
				$term_key = wpmn_term_exists( $term_id, 'mla_academic_interests' );
				if ( empty( $term_key ) ) {
					$term_key = wpmn_insert_term( sanitize_text_field( $term_id ), 'mla_academic_interests' );
				}
				if ( ! is_wp_error( $term_key ) ) {
					$term_ids[] = intval( $term_key['term_id'] );
				} else {
					error_log( '*****CAC Academic Interests Error - bad tag*****' . var_export( $term_key, true ) );
				}
			}
		}

		// Set object terms for tags.
		$term_taxonomy_ids = wpmn_set_object_terms( $user_id, $term_ids, 'mla_academic_interests' );
		wpmn_clean_object_term_cache( $user_id, 'mla_academic_interests' );

		// Set user meta for theme query.
		delete_user_meta( $user_id, 'academic_interests' );
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			add_user_meta( $user_id, 'academic_interests', $term_taxonomy_id, false );
		}

	}
}
