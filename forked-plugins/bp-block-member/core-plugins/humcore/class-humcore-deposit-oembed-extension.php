<?php
/**
 * HumCORE Deposit Embed Class.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * oEmbed handler to respond and render single deposit item.
 *
 * @since 2.6.0
 */
class Humcore_Deposit_oEmbed_Extension extends BP_Core_oEmbed_Extension {
	/**
	 * Custom oEmbed slug endpoint.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $slug_endpoint = 'deposits';

	/**
	 * Custom hooks.
	 *
	 * @since 2.6.0
	 */
	protected function custom_hooks() {
		add_action( 'oembed_dataparse',   array( $this, 'use_custom_iframe_sandbox_attribute' ), 20, 3 );
		add_action( 'get_template_part_assets/embeds/header', array( $this, 'on_deposit_header' ), 10, 2 );
		add_filter( 'embed_html', array( $this, 'humcore_filter_embed_html' ), 10, 4 );
		add_filter( 'humcore_deposit_embed_html', array( $this, 'modify_iframe' ) );
		wp_embed_register_handler( 'deposit', '#^https?://.+?/deposits/item/([^/]+)/?$#i', array( $this, 'deposit_embed_handler' ) );
		add_filter( 'oembed_request_post_id', array( $this, 'humcore_oembed_request_post_id' ), 10, 2 );
		add_filter( 'post_embed_url', array( $this, 'humcore_post_embed_url' ), 10, 2 );

	}


	/**
	 * Add custom endpoint arguments.
	 *
	 * Currently, includes 'hide_media'.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	protected function set_route_args() {
		return array(
			'hide_media' => array(
				'default' => false,
				'sanitize_callback' => 'wp_validate_boolean'
			)
		);
	}

	/**
	 * Output our custom embed template part.
	 *
	 * @since 2.6.0
	 */
	protected function content() {
		bp_get_asset_template_part( 'embeds/deposit' );
echo "CONTENT";
	}

	/**
	 * Check if we're on our single activity page.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	protected function is_page() {

		global $wp;
		if ( ! empty( $wp->query_vars['pagename'] ) ) {
			if ( 'deposits/item' == $wp->query_vars['pagename'] ) {
				if ( 'new' != $wp->query_vars['deposits_item'] &&
						! in_array( $wp->query_vars['deposits_command'], array( 'edit', 'embed', 'review' ) ) ) {
					return true;
				}
			}
		}
		return false;

	}

	/**
	 * Validates the URL to determine if the deposit item is valid.
	 *
	 * @since 2.6.0
	 *
	 * @param  string   $url The URL to check.
	 * @return int|bool Activity ID on success; boolean false on failure.
	 */
	protected function validate_url_to_item_id( $url ) {
		$domain = bp_get_root_domain();

		// Check the URL to see if this is a single deposit URL.
		if ( 0 !== strpos( $url, $domain ) ) {
			return false;
		}

		// Check for deposit item slug.
		 if ( ! preg_match( '~(deposits/item)/([^/]+)(/(embed))?/?~i', $url, $matches ) ) {
			return false;
		}

		$deposit_pid = $matches[2];

		if ( ! empty( $deposit_pid ) ) {
			return $deposit_pid;
		}

		return false;
	}

	/**
	 * Sets the oEmbed response data for our deposit item.
	 *
	 * @since 2.6.0
	 *
	 * @param  int $item_id The deposit PID.
	 * @return array
	 */
	protected function set_oembed_response_data( $item_id ) {

		$post_name = str_replace( ':', '', $item_id );
		$args = array(
			'name'           => $post_name,
			'post_type'      => 'humcore_deposit',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);

		$deposit_post = get_posts( $args );

		if ( empty( $deposit_post ) ) {
			return false;
		}

		if ( 0 == $deposit_post[0]->post_parent ) {
			$post_id = $deposit_post[0]->ID;
		} else {
			$post_id = $deposit_post[0]->post_parent;
		}

		$post_data     = get_post( $post_id );
		$post_metadata = json_decode( get_post_meta( $post_data->ID, '_deposit_metadata', true ), true );

		if ( empty( $post_metadata ) ) {
			return false;
		}
		$file_metadata = json_decode( get_post_meta( $post_id, '_deposit_file_metadata', true ), true );

		$pid           = $post_metadata['pid'];
		$content       = $post_metadata['abstract'];
		$title         = $post_data->post_title;
		$author_name   = bp_core_get_user_displayname( $post_metadata['submitter'] );
		$author_url    = bp_core_get_user_domain( $post_metadata['submitter'] );

		$data =  array(
			'content'        => $content,
			'title'          => $title,
			'author_name'    => $author_name,
			'author_url'     => $author_url,
			// Custom identifiers.
			'x_humcore_post' => $post_id,
			'x_humcore_blog' => get_current_blog_id(),
			'x_buddypress'   => 'deposits'
		);

		return $data;
	}

	/**
	 * Sets a custom <blockquote> for our oEmbed fallback HTML.
	 *
	 * @since 2.6.0
	 *
	 * @param  int $item_id The activity ID.
	 * @return string
	 */
	protected function set_fallback_html( $item_id ) {

		$post_name = str_replace( ':', '', $item_id );
		$args = array(
			'name'           => $post_name,
			'post_type'      => 'humcore_deposit',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);

		$deposit_post = get_posts( $args );

		if ( empty( $deposit_post ) ) {
			return false;
		}

		if ( 0 == $deposit_post[0]->post_parent ) {
			$post_id = $deposit_post[0]->ID;
		} else {
			$post_id = $deposit_post[0]->post_parent;
		}

		$post_data     = get_post( $post_id );
		$post_metadata = json_decode( get_post_meta( $post_data->ID, '_deposit_metadata', true ), true );

		if ( empty( $post_metadata ) ) {
			return false;
		}
		$file_metadata = json_decode( get_post_meta( $post_id, '_deposit_file_metadata', true ), true );

		$pid           = $post_metadata['pid'];
		$content       = $post_metadata['abstract'];
		$title         = $post_data->post_title;
		$author_name   = bp_core_get_user_displayname( $post_metadata['submitter'] );
		$author_url    = bp_core_get_user_domain( $post_metadata['submitter'] );
		$deposit_site  = get_site( get_current_blog_id() );
		$permalink     = sprintf( 'https://%1$s/deposits/item/%2$s/', $deposit_site->domain, $item_id );

		// 'wp-embedded-content' CSS class is necessary due to how the embed JS works.
		$blockquote = sprintf( '<blockquote class="wp-embedded-content humcore-deposit-item">%1$s%2$s %3$s</blockquote>',
			$content,
			'- ' . $author_name,
			'<a href="' . esc_url( $permalink ) . '">' . '</a>'
		);

		/**
		 * TODO Filters the fallback HTML used when embedding a HumCORE deposit item.
		 *
		 * @since 2.6.0
		 *
		 * @param string               $blockquote Current fallback HTML
		 * @param HumCORE_Deposit      $deposit    Deposit object
		 */
		return apply_filters( 'humcore_deposit_embed_fallback_html', $blockquote, $item_id );
	}

	/**
	 * Sets a custom <iframe> title for our oEmbed item.
	 *
	 * @since 2.6.0
	 *
	 * @param  int $item_id The Deposit PID
	 * @return string
	 */
	protected function set_iframe_title( $item_id ) {
		return __( 'Embedded Deposit Item', 'buddypress' );
	}

	/**
	 * Use our custom <iframe> sandbox attribute in our oEmbed response.
	 *
	 * WordPress sets the <iframe> sandbox attribute to 'allow-scripts' regardless
	 * of whatever the oEmbed response is in {@link wp_filter_oembed_result()}. We
	 * need to add back our custom sandbox value so links will work.
	 *
	 * @since 2.6.0
	 *
	 *TODO @see BP_Activity_Component::modify_iframe() where our custom sandbox value is set.
	 *
	 * @param string $result The oEmbed HTML result.
	 * @param object $data   A data object result from an oEmbed provider.
	 * @param string $url    The URL of the content to be embedded.
	 * @return string
	 */
	public function use_custom_iframe_sandbox_attribute( $result, $data, $url ) {
		// Make sure we are on a BuddyPress activity oEmbed request.
		if ( false === isset( $data->x_buddypress ) || 'deposit' !== $data->x_buddypress ) {
			return $result;
		}

		// Get unfiltered sandbox attribute from our own oEmbed response.
		$sandbox_pos = strpos( $data->html, 'sandbox=' ) + 9;
		$sandbox = substr( $data->html, $sandbox_pos, strpos( $data->html, '"', $sandbox_pos ) - $sandbox_pos );

		// Replace only if our sandbox attribute contains 'allow-top-navigation'.
		if ( false !== strpos( $sandbox, 'allow-top-navigation' ) ) {
			$result = str_replace( ' sandbox="allow-scripts"', " sandbox=\"{$sandbox}\"", $result );

			// Also remove 'security' attribute; this is only used for IE < 10.
			$result = str_replace( 'security="restricted"', "", $result );
		}

		return $result;
	}

	/**
	 * Modify various IFRAME-related items if embeds are allowed.
	 *
	 * HTML modified:
	 *  - Add sandbox="allow-top-navigation" attribute. This allows links to work
	 *    within the iframe sandbox attribute.
	 *
	 * JS modified:
	 *  - Remove IFRAME height restriction of 1000px. Fixes long embed items being
	 *    truncated.
	 *
	 * @since 2.6.0
	 *
	 * @param  string $retval Current embed HTML.
	 * @return string
	 */
	public function modify_iframe( $retval ) {
		// Add 'allow-top-navigation' to allow links to be clicked.
		$retval = str_replace( 'sandbox="', 'sandbox="allow-top-navigation ', $retval );

		// See /wp-includes/js/wp-embed.js.
		if ( SCRIPT_DEBUG ) {
			// Removes WP's hardcoded IFRAME height restriction.
			$retval = str_replace( 'height = 1000;', 'height = height;', $retval );

		// This is for the WP build minified version.
		} else {
			$retval = str_replace( 'g=1e3', 'g=g', $retval );
		}

		return $retval;
	}

    /**
     * Fetch our oEmbed response data to return.
     *
     * A simplified version of {@link get_oembed_response_data()}.
     *
     * @since BuddyPress 2.6.0
     *
     * @link http://oembed.com/ View the 'Response parameters' section for more details.
     *
     * @param array $item  Custom oEmbed response data.
     * @param int   $width The requested width.
     * @return array
     */
    protected function get_oembed_response_data( $item, $width ) {
        $data = wp_parse_args( $item, array(
            'version'       => '1.0',
            'provider_name' => get_bloginfo( 'name' ),
            'provider_url'  => get_home_url(),
            'author_name'   => get_bloginfo( 'name' ),
            'author_url'    => get_home_url(),
            'title'         => ucfirst( $this->slug_endpoint ),
            'type'          => 'rich',
        ) );
 
        /** This filter is documented in /wp-includes/embed.php */
        $min_max_width = apply_filters( 'oembed_min_max_width', array(
            'min' => 200,
            'max' => 800
        ) );
//TODO get deposit content ?
        $width  = min( max( $min_max_width['min'], $width ), $min_max_width['max'] );
        $height = max( ceil( $width / 16 * 9 ), 200 );
 
        $data['width']  = absint( $width );
        $data['height'] = absint( $height );
 
/*
        // Set 'html' parameter.
        if ( 'video' === $data['type'] || 'rich' === $data['type'] ) {
            // Fake a WP post so we can use get_post_embed_html().
            $post = new stdClass;
            $post->post_content = $data['content'];
            $post->post_title   = $data['title'];
 
            $data['html'] = get_post_embed_html( $data['width'], $data['height'], $post );
        }
*/
 
        return $data;
    }

    /**
     * Callback for the API endpoint.
     *
     * Returns the JSON object for the item.
     *
     * @since BuddyPress 2.6.0
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|array oEmbed response data or WP_Error on failure.
     */
    public function get_item( $request ) {
        $url = $request['url'];
 
        $data = false;
 
        $item_id = $this->validate_url_to_item_id( $url );
 
        if ( ! empty( $item_id ) ) {
            // Add markers to tell that we're embedding a single item.
            // This is needed for various oEmbed response data filtering.
            if ( empty( buddypress()->{$this->slug_endpoint} ) ) {
                buddypress()->{$this->slug_endpoint} = new stdClass;
            }
            buddypress()->{$this->slug_endpoint}->embedurl_in_progress = $url;
            buddypress()->{$this->slug_endpoint}->embedid_in_progress  = $item_id;
 
            // Save custom route args as well.
            $custom_args = array_keys( (array) $this->set_route_args() );
            if ( ! empty( $custom_args ) ) {
                buddypress()->{$this->slug_endpoint}->embedargs_in_progress = array();
 
                foreach( $custom_args as $arg ) {
                    if ( isset( $request[ $arg ] ) ) {
                        buddypress()->{$this->slug_endpoint}->embedargs_in_progress[ $arg ] = $request[ $arg ];
                    }
                }
            }
 
            // Grab custom oEmbed response data.
            $item = $this->set_oembed_response_data( $item_id );
            // Set oEmbed response data.
            $data = $this->get_oembed_response_data( $item, $request['maxwidth'] );
        }
	unset( $data['x_humcore_post'] ); 
	unset( $data['x_humcore_blog'] ); 

        if ( ! $data ) {
            return new WP_Error( 'oembed_invalid_url', get_status_header_desc( 404 ), array( 'status' => 404 ) );
        }
 
        return $data;
    }

        /**
         * Pass our BuddyPress activity permalink for embedding.
         *
         * @since 2.6.0
         *
         * @see bp_activity_embed_rest_route_callback()
         *
         * @param string $retval Current embed URL.
         * @return string
         */
        public function filter_embed_url( $retval ) {
//TODO remove function?
return $retval;

                if ( false === isset( buddypress()->{$this->slug_endpoint}->embedurl_in_progress ) && ! $this->is_page() ) {
                        return $retval;
                }

                $url = $this->is_page() ? $this->set_permalink() : buddypress()->{$this->slug_endpoint}->embedurl_in_progress;
                $url = trailingslashit( $url );

                // This is for the 'WordPress Embed' block
                // @see bp_activity_embed_comments_button().
                if ( 'the_permalink' !== current_filter() ) {
                        $url = add_query_arg( 'embed', 'true', trailingslashit( $url ) );

                        // Add custom route args to iframe.
                        if ( ! empty( buddypress()->{$this->slug_endpoint}->embedargs_in_progress ) ) {
                                foreach( buddypress()->{$this->slug_endpoint}->embedargs_in_progress as $key => $value ) {
                                        $url = add_query_arg( $key, $value, $url );
                                }
                        }
                }

                return $url;
        }

        /**
         * Filters the embed HTML for our BP oEmbed endpoint.
         *
         * @since 2.6.0
         *
         * @param string $retval Current embed HTML.
         * @return string
         */
        public function filter_embed_html( $retval ) {
//TODO remove function?
return $retval;
                if ( false === isset( buddypress()->{$this->slug_endpoint}->embedurl_in_progress ) && ! $this->is_page() ) {
                        return $retval;
                }

                $url = $this->set_permalink();

                $item_id = $this->is_page() ? $this->validate_url_to_item_id( $url ) : buddypress()->{$this->slug_endpoint}->embedid_in_progress;

                // Change 'Embedded WordPress Post' to custom title.
                $custom_title = $this->set_iframe_title( $item_id );
                if ( ! empty( $custom_title ) ) {
                        $title_pos = strpos( $retval, 'title=' ) + 7;
                        $title_end_pos = strpos( $retval, '"', $title_pos );

                        $retval = substr_replace( $retval, esc_attr( $custom_title ), $title_pos, $title_end_pos - $title_pos );
                }

                // Add 'max-width' CSS attribute to IFRAME.
                // This will make our oEmbeds responsive.
                if ( false === strpos( $retval, 'style="max-width' ) ) {
                        $retval = str_replace( '<iframe', '<iframe style="max-width:100%"', $retval );
                }

                // Remove default <blockquote>.
                $retval = substr( $retval, strpos( $retval, '</blockquote>' ) + 13 );

                // Set up new fallback HTML
                // @todo Maybe use KSES?
                $fallback_html = $this->set_fallback_html( $item_id );

                /**
                 * Dynamic filter to return BP oEmbed HTML.
                 *
                 * @since 2.6.0
                 *
                 * @var string $retval
                 */
                return apply_filters( "bp_{$this->slug_endpoint}_embed_html", $fallback_html . $retval );
        }

	/**
	 * Filter the oembed html output due to funky BP post construction
	 *
	 * @param string  $output Default output text for current post.
	 * @param WP_Post $post   Current post object.
	 * @param int     $width  Width of the response.
	 * @param int     $height Height of the response.
	 * @return string Filtered output for deposit items.
	 */
	public function humcore_filter_embed_html( $output, $post, $width, $height ) {

		if ( 'humcore_deposit' !== $post->post_type ) {
			return $output;
		}
		$deposit_pid = preg_replace( '/(.+?)(\d+)/i', '${1}:${2}', $post->post_name );
		$output = preg_replace( '~humcore_deposit/.+?\d+?/(embed/)?~i', 'deposits/item/' . $deposit_pid . '/${1}' , $output );

		return $output;
	}

	/**
	 * Filter the post url due to funky BP post construction
	 *
	 * @param string  $embed_url The post embed URL.
	 * @param WP_Post $post      The corresponding post object.
	 * @return string Filtered url for deposit item.
	 */
	public function humcore_post_embed_url( $url, $post ) {

		if ( 'humcore_deposit' !== $post->post_type ) {
			return $url;
		}

		$deposit_pid = preg_replace( '/(.+?)(\d+)/i', '${1}:${2}', $post->post_name );
		$site_url = get_option( 'siteurl' );
		$url = sprintf( '%s/deposits/item/%s/', $site_url, $deposit_pid );

		return $url;

	}

	/**
	 * Do stuff when our oEmbed deposit header template part is loading.
	 *
	 * TODO Currently, removes wpautop() from the bp_activity_action() function.
	 *
	 * @since 2.6.0
	 *
	 * @param string $slug Template part slug requested.
	 * @param string $name Template part name requested.
	 */
	public function on_deposit_header( $slug, $name ) {
		if ( false === $this->is_page() || 'deposit' !== $name ) {
			return;
		}

		//TODO remove_filter( 'bp_get_activity_action', 'wpautop' );
	}

	public function humcore_oembed_request_post_id( $post_id, $url ) {

		// Check for deposit item slug.
		if ( ! preg_match( '~(deposits/item)/([^/]+)/?(\?(embed[^\&]+))?\&?~i', $url, $matches ) ) {
			return $post_id;
		}

		$deposit_pid = $matches[2];
		if ( empty( $deposit_pid ) ) {
			return $post_id;
		}

		//figure out network of deposit
		$humcore_domain = parse_url( $url );
		$site_query = get_sites( array( 'domain' => $humcore_domain['host'], 'number' => 1 ) );
		$humcore_site = reset( $site_query );

		$switched = false;
		if ( get_current_blog_id() != $humcore_site->blog_id ) {
			switch_to_blog( $humcore_site->blog_id );
			$switched = true;
		}

		$post_name = str_replace( ':', '', $deposit_pid );
		$args = array(
			'name'           => $post_name,
			'post_type'      => 'humcore_deposit',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);
		$deposit_post = get_posts( $args );

		if ( $switched ) {
			restore_current_blog();
		}
		if ( empty( $deposit_post ) ) {
			return $post_id;
		}
		if ( 0 == $deposit_post[0]->post_parent ) {
			$post_id = $deposit_post[0]->ID;
		} else {
			$post_id = $deposit_post[0]->post_parent;
		}

		return $post_id;

	}

	/**
	 * Output the deposit resource embed
	 */
	public function deposit_embed_handler($matches, $attr, $url, $rawattr) {

		$wp_referer = wp_get_referer();

		// Check for deposit item slug.
		 if ( ! preg_match( '~(deposits/item)/([^/]+)/?(\?(embed[^\&]+))?\&?~i', $url, $matches ) ) {
			return false;
		}

		$deposit_pid = $matches[2];
		if ( empty( $deposit_pid ) ) {
			return false;
		}

		//figure out network of deposit
		$humcore_domain = parse_url( $url );
		$site_query = get_sites( array( 'domain' => $humcore_domain['host'], 'number' => 1 ) );
		$humcore_site = reset( $site_query );

		$switched = false;
		if ( get_current_blog_id() != $humcore_site->blog_id ) {
			switch_to_blog( $humcore_site->blog_id );
			$switched = true;
		}

		// Grab custom oEmbed response data.
		$item = $this->set_oembed_response_data( $deposit_pid );

		if ( ! $item ) {
			return false;
		}

		// Set oEmbed response data.
		//$data = $this->get_oembed_response_data( $item, $request['maxwidth'] );
		$data = $this->get_oembed_response_data( $item, '800' );
		if ( ! $data ) {
			return false;
		}

		$post_data     = get_post( $data['x_humcore_post'] );
		$file_metadata = json_decode( get_post_meta( $post_data->ID, '_deposit_file_metadata', true ), true );

		if ( empty( $file_metadata ) ) {
			return;
		}

		$embeds_meta_key = sprintf( '_total_embeds_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
                $total_embeds = get_post_meta( $post_data->ID, $embeds_meta_key, true ) + 1; // Downloads counted at file level.
                if ( get_current_user_id() != $post_data->post_author && /* ! humcore_is_bot_user_agent() && */ false === $wp_referer ) {
                        $post_meta_id = update_post_meta( $post_data->ID, $embeds_meta_key, $total_embeds );
                }

		$site_url = get_option( 'siteurl' );
		$view_url = sprintf(
			'%s/deposits/view/%s/%s/%s/',
			$site_url,
			$file_metadata['files'][0]['pid'],
			$file_metadata['files'][0]['datastream_id'],
			$file_metadata['files'][0]['filename']
		);

		$view_url = sprintf(
			'%1$s/deposits/objects/%2$s/datastreams/CONTENT/content',
			$site_url,
			$file_metadata['files'][0]['pid']
		);

		if ( in_array( $file_metadata['files'][0]['filetype'], array( 'application/pdf', 'text/html', 'text/plain' ) ) ) { 
			$embed = sprintf(
				'<iframe width="%s" height="%s" src="%s/app/plugins/humcore/pdf-viewer/web/viewer.html?file=%s&download=false&print=false&openfile=false"></iframe>',
				$data['width'],
				$data['height'],
				$site_url,
				urlencode( $view_url )
			);
		} else if ( in_array( strstr( $file_metadata['files'][0]['filetype'], '/', true ), array( 'audio', 'image', 'video' ) ) ) {
			$embed = sprintf(
				'<iframe width="%s" height="%s" src="%s"></iframe>',
				$data['width'],
				$data['height'],
				$view_url
			);
		} else {
			$embed = sprintf(
				'<iframe src="https://docs.google.com/viewer?url=%s&embedded=true" style="width:%s height:%s" frameborder="0"></iframe>',
				$view_url,
				$data['width'],
				$data['height']
			);
		}

                if ( $switched ) {
                        restore_current_blog();
                }

		return $embed;

	}

    /**
     * Inject content into the embed template.
     *
     * @since BuddyPress 2.6.0
     */
    public function inject_content() {
//        if ( ! $this->is_page() ) {
//            return;
//        }
 
        $this->content();
    }
}
