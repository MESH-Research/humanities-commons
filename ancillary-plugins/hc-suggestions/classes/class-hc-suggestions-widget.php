<?php
/**
 * Widget.
 *
 * @package HC_Suggestions
 */

/**
 * Widget.
 */
class HC_Suggestions_Widget extends WP_Widget {

	/**
	 * Name of the taxonomy from which to get user's selected terms
	 */
	const TAXONOMY = 'mla_academic_interests';

	/**
	 * Post types supported by the widget.
	 *
	 * Identifier => label.
	 *
	 * Would be nice to pull labels from an authoritative source rather than hardcode,
	 * but that doesn't exist for fake post types anyway.
	 *
	 * @var array
	 */
	public $post_types = [
		'user'            => 'Members',
		'bp_group'        => 'Groups',
		'humcore_deposit' => 'Scholarship',
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'HC_Suggestions_Widget',
			'HC Suggestions Widget',
			[
				'classname'   => 'HC_Suggestions_Widget',
				'description' => 'Suggest content to members based on selected terms.',
			]
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		if (
			( isset( $instance['show_when_logged_in'] ) && $instance['show_when_logged_in'] && ! is_user_logged_in() ) ||
			( isset( $instance['show_when_logged_out'] ) && $instance['show_when_logged_out'] && is_user_logged_in() )
		) {
			return;
		}

		$tab_id_prefix = 'hc-suggestions-tab-';

		$user_terms = wpmn_get_object_terms(
			get_current_user_id(),
			self::TAXONOMY,
			[
				'fields' => 'names',
			]
		);

		echo '<div class="hc-suggestions-widget widget">'; // main widget container.

		if ( ! empty( $instance['title'] ) ) {
			printf(
				'<h3 class="widget-title">%s</h3>',
				$instance['title']
			);
		}

		if ( ! empty( $instance['description'] ) ) {
			printf(
				'<p class="widget-description">%s</p>',
				$instance['description']
			);
		}

		echo '<ul>'; // open tabs.

		// tabs.
		foreach ( $this->post_types as $identifier => $label ) {
			if ( $instance[ $identifier . '_tab_enabled' ] ) {
				printf(
					'<li><a href="#%s">%s</a></li>',
					esc_attr( $tab_id_prefix . $identifier ),
					$label
				);
			}
		}

		echo '</ul>'; // close tabs.

		// results containers.
		foreach ( $this->post_types as $identifier => $label ) {
			if ( $instance[ $identifier . '_tab_enabled' ] ) {
				printf(
					'<div id="%s" data-hc-suggestions-query="%s" data-hc-suggestions-type="%s"></div>',
					esc_attr( $tab_id_prefix . $identifier ),
					implode( ' ', $user_terms ),
					$identifier
				);
			}
		}

		echo '</div>'; // close hc-suggestions-widget.
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options.
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'                       => 'Recommended for You',
			'description'                 => '',
			'show_when_logged_in'         => true,
			'show_when_logged_out'        => true,
			'user_tab_enabled'            => true,
			'bp_group_tab_enabled'        => true,
			'humcore_deposit_tab_enabled' => true,
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title                       = strip_tags( $instance['title'] );
		$description                 = strip_tags( $instance['description'] );
		$show_when_logged_in         = (bool) $instance['show_when_logged_in'];
		$show_when_logged_out        = (bool) $instance['show_when_logged_out'];
		$user_tab_enabled            = (bool) $instance['user_tab_enabled'];
		$bp_group_tab_enabled        = (bool) $instance['bp_group_tab_enabled'];
		$humcore_deposit_tab_enabled = (bool) $instance['humcore_deposit_tab_enabled'];
		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddypress' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>

		<p><label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Description:', 'buddypress' ); ?> </label><textarea class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>"><?php echo esc_attr( $description ); ?></textarea></p>

		<h3>Visibility</h3>

		<p><label for="<?php echo $this->get_field_id( 'show_when_logged_in' ); ?>"><input type="checkbox" name="<?php echo $this->get_field_name( 'show_when_logged_in' ); ?>" id="<?php echo $this->get_field_id( 'show_when_logged_in' ); ?>" value="1" <?php checked( (bool) $instance['show_when_logged_in'] ); ?> /> <?php echo 'Show When Logged In'; ?></label></p>

		<p><label for="<?php echo $this->get_field_id( 'show_when_logged_out' ); ?>"><input type="checkbox" name="<?php echo $this->get_field_name( 'show_when_logged_out' ); ?>" id="<?php echo $this->get_field_id( 'show_when_logged_out' ); ?>" value="1" <?php checked( (bool) $instance['show_when_logged_out'] ); ?> /> <?php echo 'Show When Logged Out'; ?></label></p>

		<h3>Tabs</h3>

		<?php foreach ( $this->post_types as $identifier => $label ) : ?>
			<?php $option_name = $identifier . '_tab_enabled'; ?>
			<p><label for="<?php echo $this->get_field_id( $option_name ); ?>"><input type="checkbox" name="<?php echo $this->get_field_name( $option_name ); ?>" id="<?php echo $this->get_field_id( $option_name ); ?>" value="1" <?php checked( (bool) $instance[ $option_name ] ); ?> /> <?php echo "<strong>$label</strong> Tab Enabled"; ?></label></p>
		<?php endforeach; ?>

		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$defaults = array(
			'title'                       => 'Recommended for You',
			'description'                 => '',
			'show_when_logged_in'         => true,
			'show_when_logged_out'        => true,
			'user_tab_enabled'            => true,
			'bp_group_tab_enabled'        => true,
			'humcore_deposit_tab_enabled' => true,
		);

		$new_instance = wp_parse_args( (array) $new_instance, $defaults );

		$instance['title']                       = strip_tags( $new_instance['title'] );
		$instance['description']                 = strip_tags( $new_instance['description'] );
		$instance['show_when_logged_in']         = (bool) $new_instance['show_when_logged_in'];
		$instance['show_when_logged_out']        = (bool) $new_instance['show_when_logged_out'];
		$instance['user_tab_enabled']            = (bool) $new_instance['user_tab_enabled'];
		$instance['bp_group_tab_enabled']        = (bool) $new_instance['bp_group_tab_enabled'];
		$instance['humcore_deposit_tab_enabled'] = (bool) $new_instance['humcore_deposit_tab_enabled'];

		return $instance;
	}

}
