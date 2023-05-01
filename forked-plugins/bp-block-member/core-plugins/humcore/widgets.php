<?php
// phpcs:ignoreFile -- this file will be refactored
/**
 * HumCore Widgets.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Facets Widget
 */
class Humcore_Deposits_Search_Facets_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	function __construct() {
		$widget_ops                       = array(
			'description' => __( '(HumCORE) Faceted Search Results', 'humcore_domain' ),
			'classname'   => 'widget_deposits_search_facets_widget',
		);
		parent::__construct( false, $name = _x( '(HumCORE) Faceted Search Results', 'widget name', 'humcore_domain' ), $widget_ops );
	}

	/**
	 * Display the Faceted Search Results widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		echo $args['before_title'] .
			$title .
			$args['after_title']; ?>
		<div class="search-facets facet-set" role="navigation"><h5><label for="search-facets"><?php _e( 'Filter results (select all that apply)', 'humcore_domain' ); ?></label></h5>
		<?php humcore_search_sidebar_content(); ?>
		</div>

		<?php
		echo $args['after_widget'];
	}

	/**
	 * Update the Faceted Search Results widget options.
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Output the Faceted Search Results widget options form.
	 *
	 * @param $instance Settings for this widget.
	 */
	function form( $instance ) {
		$defaults = array(
			'title' => __( 'Search Results', 'humcore_domain' ),
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = strip_tags( $instance['title'] );
		?>

		<p><label for="bp-core-widget-title"><?php _e( 'Title:', 'humcore_domain' ); ?>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
		</label></p>

	<?php
	}
}
add_action(
	'widgets_init', function() {
		register_widget( 'Humcore_Deposits_Search_Facets_Widget' );
	}
);

/**
 * Directory Sidebar Widget
 */
class Humcore_Deposits_Directory_Sidebar_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	function __construct() {
		$widget_ops                       = array(
			'description' => __( '(HumCORE) Deposits Directory Sidebar', 'humcore_domain' ),
			'classname'   => 'widget_deposits_directory_sidebar_widget',
		);
		parent::__construct( false, $name = _x( '(HumCORE) Deposits Directory Sidebar', 'widget name', 'humcore_domain' ), $widget_ops );
	}

	/**
	 * Display the Faceted Search Results widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		echo $args['before_title'] .
			$title .
			$args['after_title'];
			?>
		<div class="directory-facets facet-set" role="navigation">
		<?php humcore_directory_sidebar_content(); ?>
		</div>

		<?php
		echo $args['after_widget'];
	}

	/**
	 * Update the Faceted Search Results widget options.
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Output the Faceted Search Results widget options form.
	 *
	 * @param $instance Settings for this widget.
	 */
	function form( $instance ) {
		$defaults = array(
			'title' => __( 'Deposits Directory Sidebar', 'humcore_domain' ),
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = strip_tags( $instance['title'] );
		?>

		<p><label for="bp-core-widget-title"><?php _e( 'Title:', 'humcore_domain' ); ?>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
		</label></p>

	<?php
	}
}
add_action(
	'widgets_init', function() {
		register_widget( 'Humcore_Deposits_Directory_Sidebar_Widget' );
	}
);
