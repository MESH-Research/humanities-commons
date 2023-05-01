<?php
/**
 * Customizations to BuddyPress Functions.
 *
 * @package Hc_Custom
 */

/**
 * Filters bp_legacy_object_template_path to fix group member directory bug.
 *
 * @param string $template_path Template Directory.
 */
function hc_custom_bp_legacy_object_template_path( $template_path ) {

	if ( ! empty( $_POST['template'] ) && 'groups/single/members' === $_POST['template'] ) {
		$template_part = 'groups/single/members.php';
		$template_path = bp_locate_template( array( $template_part ), false );
	}

	return $template_path;
}

add_filter( 'bp_legacy_object_template_path', 'hc_custom_bp_legacy_object_template_path' );


/**
 * Output the wrapper markup for the blog signup form.
 *
 * @param string          $blogname   Optional. The default blog name (path or domain).
 * @param string          $blog_title Optional. The default blog title.
 * @param string|WP_Error $errors     Optional. The WP_Error object returned by a previous
 *                                    submission attempt.
 */
function hc_custom_bp_show_blog_signup_form($blogname = '', $blog_title = '', $errors = '') {
 global $current_user;

    if ( ! Humanities_Commons::hcommons_user_in_current_society() && ! is_super_admin() ) {
        $society_name = Humanities_Commons::society_name();
        echo "<p>Only members of $society_name can create a new site on this network.</p>";
        return;
    }

    if ( isset($_POST['submit']) ) {
        // Updated for BP 9.0.0 compatibility, following /srv/www/commons/current/web/app/plugins/buddypress/bp-blogs/bp-blogs-template.php
        $blog_id = bp_blogs_validate_blog_signup();
        if ( is_numeric( $blog_id ) ) {
            $site = get_site( $blog_id );

            if ( isset( $site->id ) && $site->id ) {
                $current_user = wp_get_current_user();

                bp_blogs_confirm_blog_signup(
                    $site->domain,
                    $site->path,
                    $site->blogname,
                    $current_user->user_login,
                    $current_user->user_email,
                    '',
                    $site->id
                );
            }
        }
    } 
    
    if ( ! isset( $_POST['submit'] ) || ! isset( $blog_id ) || false === $blog_id || is_wp_error( $blog_id ) ) {
        if ( isset( $blog_id ) && is_wp_error( $blog_id ) ) {
			$errors = $blog_id;
		} elseif ( ! is_wp_error($errors) ) {
            $errors = new WP_Error();
        }

        /**
         * Filters the default values for Blog name, title, and any current errors.
         *
         * @since BuddyPress 1.0.0
         *
         * @param array $value {
         *      string   $blogname   Default blog name provided.
         *      string   $blog_title Default blog title provided.
         *      WP_Error $errors     WP_Error object.
         * }
         */
        $filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
        $blogname = $filtered_results['blogname'];
        $blog_title = $filtered_results['blog_title'];
        $errors = $filtered_results['errors'];
 
        if ( $errors->get_error_code() ) {
            echo "<p>" . __('There was a problem; please correct the form below and try again.', 'buddyboss') . "</p>";
        }
        ?>
        <p><?php _e("By filling out the form below, you can <strong>add a site to your account</strong>. There is no limit to the number of sites that you can have, so create to your heart's content, but create responsibly!</p>", 'buddyboss'); ?>
        <p><?php _e("<strong>Note:</strong> If you're not going to use a great domain, leave it for a new user.</p>", 'buddyboss'); ?>
        
        <form class="standard-form" id="setupform" method="post" action="">
 
            <input type="hidden" name="stage" value="gimmeanotherblog" />
            
            <?php hc_custom_bp_blogs_signup_blog($blogname, $blog_title, $errors); ?>

            <p>
                <input id="submit" type="submit" name="submit" class="submit" value="<?php esc_attr_e('Create Site', 'buddyboss') ?>" />
            </p>

            <?php
            $society_id = strtoupper( Humanities_Commons::$society_id );
            if ( $society_id == 'HC' || $society_id == 'MSU') {
                $acceptable_use_link = 'https://' . bp_signup_get_subdomain_base() . 'website-policy';
                echo Humanities_Commons::society_name();
                echo "<a href='$acceptable_use_link'> ";
                _e( 'acceptable use policy', 'buddypress' );
                echo "</a>.";
            }
            ?>
 
            <?php wp_nonce_field( 'bp_blog_signup_form' ) ?>
        </form>
        <?php
    }
}

/**
 * This creates a custom site privacy form. 
 *
 * It replaces the one created by the More Privacy Options plugin. This form
 * appears on the 'Create a Site' page. It formerly appeared on the group site
 * page and was removed. It was formerly triggered by the 'signup_blogform'
 * action but is now called directly.
 *
 * @author Mike Thicke
 */
function hc_custom_site_creation_privacy_form() {
    global $details,$options;
    $society_id = strtoupper( Humanities_Commons::$society_id );
    ?>
    <p><strong>Site Privacy</strong></p>
    <p>These settings may be changed in your admin panel under "Settings/Reading/Site Visibility."</p>
    <fieldset class="create-site">
        <label class="checkbox">
            <input type="radio" name="blog_public" value="1" <?php if( !isset( $_POST['blog_public'] ) || '1' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
            <?php _e( 'Public and allow search engines to index this site. (Note: It is up to search engines to honor your request. The site will appear in public listings around Humanities Commons.)' , 'buddyboss'); ?>
        </label>
        <label class="checkbox">
            <input type="radio" name="blog_public" value="0" <?php if( !isset( $_POST['blog_public'] ) || '0' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
            <?php _e( 'Public but discourage search engines from index this site. (Note: This option does not block access to your site — it is up to search engines to honor your request. The site will appear in public listings around Humanities Commons.)' , 'buddyboss'); ?>
        </label>
        <label class="checkbox">
            <input type="radio" name="blog_public" value="-1" <?php if( !isset( $_POST['blog_public'] ) || '-1' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
            <?php 
            _e( 'Visible only to registered users of ' , 'buddyboss'); 
            echo( $society_id );
            ?>
        </label>
        <label class="checkbox">
            <input type="radio" name="blog_public" value="-2" <?php if( !isset( $_POST['blog_public'] ) || '-2' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
            <?php _e( 'Visible only to registered users of this site' , 'buddyboss'); ?>
        </label>
        <label class="checkbox">
            <input type="radio" name="blog_public" value="-3" <?php if( !isset( $_POST['blog_public'] ) || '-3' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
            <?php _e( 'Visible only to administrators of this site' , 'buddyboss'); ?>
        </label>
    </fieldset>
    <?php
}

/**
 * This removes the More Privacy Options blog signup form, which is then
 * replaced by hc_custom_site_creation_privacy_form().
 *
 * @author Mike Thicke
 */
function hc_custom_remove_more_privacy_signup_blogform() {
    global $wp_filter;
    if ( isset( $wp_filter['signup_blogform']->callbacks ) ) {
        foreach ( $wp_filter['signup_blogform']->callbacks as $callback ) {
            if ( is_array( $callback ) ) {
                foreach ( $callback as $callback_item ) {
                    if ( is_array( $callback_item ) && is_a( $callback_item['function'][0], 'DS_More_Privacy_Options' ) ) {
                        remove_action( 'signup_blogform', [ $callback_item['function'][0], 'add_privacy_options' ] );
                        return;
                    }
                }
            }
        }
    }
}
add_action( 'signup_blogform', 'hc_custom_remove_more_privacy_signup_blogform', 1, 0 );

/**
 * This displays the 'Create a Site' page.
 */
function hc_custom_bp_blogs_signup_blog( $blogname = '', $blog_title = '', $errors = null ) {
    global $current_site;
    hc_custom_remove_more_privacy_signup_blogform();
 
    // Blog name.
    if( !is_subdomain_install() )
        echo '<strong><label for="blogname">' . __('Site Name:', 'buddyboss') . '</label></strong>';
    else
        echo '<strong><label for="blogname">' . __('Site Domain:', 'buddyboss') . '</label></strong>';
 
    if ( $errors ) { 
        $errmsg = $errors->get_error_message('blogname');
        ?>
        <p class="error"><?php echo $errmsg ?></p>
        <?php 
    }
 
    if ( !is_subdomain_install() )
        echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span> <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="63" /><br />';
    else
        echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="63" ' . bp_get_form_field_attributes( 'blogname' ) . '/> <span class="suffix_address">.' . bp_signup_get_subdomain_base() . '</span><br />';
 
    ?>
 
    <label for="blog_title"><strong><?php _e('Site Title:', 'buddyboss') ?></strong></label>
 
    <?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
 
        <p class="error"><?php echo $errmsg ?></p>
 
    <?php }
    echo '<input name="blog_title" type="text" id="blog_title" value="'.esc_html($blog_title, 1).'" /></p>';
    ?>

    <p><strong><?php _e("Are you an instructor? Is this a course site?", 'buddyboss'); ?></strong></p>
    <p><?php _e('Keep in mind that many domains may create ambiguity; rather than "hist101" you might include institution and semester information to avoid conflicts, such as "msuhist101s20."', 'buddyboss'); ?></p>
    <p><strong><?php _e('Note:', 'buddyboss'); ?></strong> <?php _e('If you check off "This is a course site," below, the Learning Space theme will be activated and the site url will be prefixed with your username (e.g. hcadmin-learningspace.hcommons.org).', 'buddyboss') ?></p>

    <label class="checkbox" for="is-classsite">
    <input type="checkbox" id="is_classsite" name="is_classsite" value="1" <?php if( isset( $_POST['is_classsite'] ) || '1' == $_POST['is_classsite'] ) { ?>checked="checked"<?php } ?> />
        <strong><?php _e( 'This is a course site' , 'buddypress'); ?></strong><br><br>
    </label>

    <?php do_action( 'signup_hidden_fields' ); ?>

    <?php

    hc_custom_site_creation_privacy_form();
}

/**
 * Ensures that a new site has a domain set. It addresses an issue encountered
 * with new MSU sites.
 *
 * This is a hack because the source of the problem can't be found. 
 * @see https://github.com/MESH-Research/hc-admin-docs-support/issues/102
 *
 * @author Mike Thicke
 * 
 * @param Array $result Result of blog validation (@see ms-functions.php::wpmu_validate_blog_signup)
 * @return Array Fixed result
 */
function hc_custom_ensure_site_has_domain( $result ) {
    if ( substr( $result['domain'], -1 ) == '.' ) {
        $result['domain'] = $result['domain'] . $_SERVER['HTTP_HOST'];
    }
    return $result;
}
add_filter( 'wpmu_validate_blog_signup', 'hc_custom_ensure_site_has_domain', 10, 1 );
