<?php
/**
 * BuddyPress component class.
 *
 * @package Hc_Member_Profiles
 */

/**
 * Component class.
 */
class HC_Member_Profiles_Component extends BP_Component {

	const NAME         = 'Name';
	const AFFILIATION  = 'Institutional or Other Affiliation';
	const TITLE        = 'Title';
	const SITE         = 'Website URL';
	const TWITTER      = '<em>Twitter</em> handle';
	const MASTODON     = 'Mastodon handle';
	const FACEBOOK     = 'Facebook URL';
	const LINKEDIN     = 'LinkedIn URL';
	const FIGSHARE     = 'Figshare URL';
	const ORCID        = '<em>ORCID</em> iD';
	const ABOUT        = 'About';
	const EDUCATION    = 'Education';
	const PUBLICATIONS = 'Publications';
	const PROJECTS     = 'Projects';
	const TALKS        = 'Upcoming Talks and Conferences';
	const MEMBERSHIPS  = 'Memberships';
	const DEPOSITS     = 'CORE Deposits';
	const CV           = 'CV';
	const INTERESTS    = 'Academic Interests';
	const GROUPS       = 'Commons Groups';
	const ACTIVITY     = 'Recent Commons Activity';
	const BLOGS        = 'Commons Sites';
	const BLOGPOSTS    = 'Blog Posts';

	/**
	 * TODO deprecate.
	 *
	 * @var array
	 */
	public static $display_names;

	/**
	 * Start the component creation process.
	 */
	public function __construct() {

		self::$display_names = [
			self::NAME         => 'Name',
			self::AFFILIATION  => 'Institutional or Other Affiliation',
			self::TITLE        => 'Title',
			self::SITE         => 'Website URL',
			self::TWITTER      => '<em>Twitter</em> handle',
			self::MASTODON     => 'Mastodon handle',
			self::FACEBOOK     => 'Facebook URL',
			self::LINKEDIN     => 'LinkedIn URL',
			self::ORCID        => '<em>ORCID</em> iD',
			self::FIGSHARE     => 'Figshare URL',
			self::ABOUT        => 'About',
			self::EDUCATION    => 'Education',
			self::PUBLICATIONS => 'Publications',
			self::PROJECTS     => 'Projects',
			self::TALKS        => 'Upcoming Talks and Conferences',
			self::MEMBERSHIPS  => 'Memberships',
			self::DEPOSITS     => 'Work Shared in CORE',
			self::CV           => 'CV',
			self::INTERESTS    => 'Academic Interests',
			self::GROUPS       => 'Commons Groups',
			self::ACTIVITY     => 'Recent Commons Activity',
			self::BLOGS        => 'Commons Sites',
			self::BLOGPOSTS    => 'Blog Posts',
		];

		parent::start(
			'hc_member_profiles',
			'HC Member Profiles',
			dirname( __DIR__ ) . '/includes'
		);

		buddypress()->active_components[ $this->id ] = '1';
	}

	/**
	 * Add custom hooks.
	 */
	public function setup_actions() {
		if ( bp_is_profile_component() && ! bp_is_user_change_avatar() && ! bp_is_user_change_cover_image() ) {
			add_action( 'wp_enqueue_scripts', 'hcmp_enqueue_scripts' );

			bp_register_template_stack(
				function() {
					return plugin_dir_path( __DIR__ ) . 'templates/';
				}
			);
		}

		// Update allowed/auto tag filtering.
		remove_filter( 'bp_get_the_profile_field_value', 'wpautop' );
		remove_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );
		add_filter( 'xprofile_allowed_tags', 'hcmp_filter_xprofile_allowed_tags' );

		// Don't log "changed their profile picture" activities.
		remove_action( 'xprofile_avatar_uploaded', 'bp_xprofile_new_avatar_activity' );
	}
}
