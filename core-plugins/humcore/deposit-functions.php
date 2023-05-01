<?php
/**
 * New deposit and edit deposit transaction related support functions.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Prepare the metadata sent to Fedora and Solr from $_POST input.
 *
 * @param string $user deposit user
 * @param array $curr_val array of $_POST entries.
 * @return array metadata content
 */
function prepare_user_entered_metadata( $user, $curr_val ) {

	/**
	 * Prepare the metadata to be sent to Fedora and Solr.
	 */
	$metadata                       = array();
	$metadata['title']              = wp_strip_all_tags( stripslashes( $curr_val['deposit-title-unchanged'] ) );
	$metadata['title_unchanged']    = wp_kses(
		stripslashes( $curr_val['deposit-title-unchanged'] ),
		array(
			'b'      => array(),
			'em'     => array(),
			'strong' => array(),
		)
	);
	$metadata['abstract']           = wp_strip_all_tags( stripslashes( $curr_val['deposit-abstract-unchanged'] ) );
	$metadata['abstract_unchanged'] = wp_kses(
		stripslashes( $curr_val['deposit-abstract-unchanged'] ),
		array(
			'b'      => array(),
			'em'     => array(),
			'strong' => array(),
		)
	);
	$metadata['genre']              = sanitize_text_field( $curr_val['deposit-genre'] );
	$metadata['deposit_for_others'] = sanitize_text_field( $curr_val['deposit-for-others-flag'] );
	$metadata['committee_deposit']  = sanitize_text_field( $curr_val['deposit-on-behalf-flag'] );
	if ( ! empty( $curr_val['deposit-committee'] ) ) {
		$metadata['committee_id'] = sanitize_text_field( $curr_val['deposit-committee'] );
	} else {
		$metadata['committee_id'] = '';
	}

	/**
	 * Get committee or author metadata.
	 */

	if ( 'yes' === $metadata['committee_deposit'] ) {
		$committee                = groups_get_group( array( 'group_id' => $metadata['committee_id'] ) );
		$metadata['organization'] = strtoupper( humcore_get_current_society_id() );
		$metadata['authors'][]    = array(
			'fullname'    => $committee->name,
			'given'       => '',
			'family'      => '',
			'uni'         => $committee->slug,
			'role'        => 'creator',
			'affiliation' => strtoupper( humcore_get_current_society_id() ),
		);
	} elseif ( 'submitter' !== sanitize_text_field( $curr_val['deposit-author-role'] ) ) {
		$user_id                  = $user->ID;
		$user_firstname           = get_the_author_meta( 'first_name', $user_id );
		$user_lastname            = get_the_author_meta( 'last_name', $user_id );
		$user_affiliation         = bp_get_profile_field_data(
			array(
				'field'   => 'Institutional or Other Affiliation',
				'user_id' => $user_id,
			)
		);
		$metadata['organization'] = $user_affiliation;
		$metadata['authors'][]    = array(
			'fullname'    => $user->display_name,
			'given'       => $user_firstname,
			'family'      => $user_lastname,
			'uni'         => $user->user_login,
			'role'        => sanitize_text_field( $curr_val['deposit-author-role'] ),
			'affiliation' => $user_affiliation,
		);
	}

	if ( ( ! empty( $curr_val['deposit-other-authors-first-name'] ) && ! empty( $curr_val['deposit-other-authors-last-name'] ) ) ) {
		$other_authors = array_map(
			function ( $first_name, $last_name, $role ) {
						return array(
							'first_name' => sanitize_text_field( $first_name ),
							'last_name'  => sanitize_text_field( $last_name ),
							'role'       => sanitize_text_field( $role ),
						); },
			$curr_val['deposit-other-authors-first-name'], $curr_val['deposit-other-authors-last-name'], $curr_val['deposit-other-authors-role']
		);
		foreach ( $other_authors as $author_array ) {
			if ( ! empty( $author_array['first_name'] ) && ! empty( $author_array['last_name'] ) ) {
				$mla_user = bp_activity_find_mentions( $author_array['first_name'] . $author_array['last_name'] );
				if ( ! empty( $mla_user ) ) {
					foreach ( $mla_user as $mla_userid => $mla_username ) {
						break;
					} // Only one, right?
					$author_name        = bp_core_get_user_displayname( $mla_userid );
					$author_firstname   = get_the_author_meta( 'first_name', $mla_userid );
					$author_lastname    = get_the_author_meta( 'last_name', $mla_userid );
					$author_affiliation = bp_get_profile_field_data(
						array(
							'field'   => 'Institutional or Other Affiliation',
							'user_id' => $mla_userid,
						)
					);
					$author_uni         = $mla_username;
				} else {
					$author_firstname   = $author_array['first_name'];
					$author_lastname    = $author_array['last_name'];
					$author_name        = trim( $author_firstname . ' ' . $author_lastname );
					$author_uni         = '';
					$author_affiliation = '';
				}
				$metadata['authors'][] = array(
					'fullname'    => $author_name,
					'given'       => $author_firstname,
					'family'      => $author_lastname,
					'uni'         => $author_uni,
					'role'        => $author_array['role'],
					'affiliation' => $author_affiliation,
				);
			}
		}
	}

	usort(
		$metadata['authors'], function( $a, $b ) {
				return strcasecmp( $a['family'], $b['family'] );
		}
	);

	/**
	 * Format author info for solr.
	 */
	$metadata['author_info'] = humcore_deposits_format_author_info( $metadata['authors'] );

	if ( ! empty( $metadata['genre'] ) && in_array( $metadata['genre'], array( 'Dissertation', 'Technical report', 'Thesis', 'White paper' ) ) &&
		! empty( $curr_val['deposit-institution'] ) ) {
		$metadata['institution'] = sanitize_text_field( $curr_val['deposit-institution'] );
	} elseif ( ! empty( $metadata['genre'] ) && in_array( $metadata['genre'], array( 'Dissertation', 'Technical report', 'Thesis', 'White paper' ) ) &&
		empty( $curr_val['deposit-institution'] ) ) {
		$metadata['institution'] = $metadata['organization'];
	}

	if ( ! empty( $metadata['genre'] ) && ( 'Conference proceeding' == $metadata['genre'] || 'Conference paper' == $metadata['genre'] || 'Conference poster' == $metadata['genre'] ) ) {
		$metadata['conference_title']        = sanitize_text_field( $curr_val['deposit-conference-title'] );
		$metadata['conference_organization'] = sanitize_text_field( $curr_val['deposit-conference-organization'] );
		$metadata['conference_location']     = sanitize_text_field( $curr_val['deposit-conference-location'] );
		$metadata['conference_date']         = sanitize_text_field( $curr_val['deposit-conference-date'] );
	}

	if ( ! empty( $metadata['genre'] ) && 'Presentation' == $metadata['genre'] ) {
		$metadata['meeting_title']        = sanitize_text_field( $curr_val['deposit-meeting-title'] );
		$metadata['meeting_organization'] = sanitize_text_field( $curr_val['deposit-meeting-organization'] );
		$metadata['meeting_location']     = sanitize_text_field( $curr_val['deposit-meeting-location'] );
		$metadata['meeting_date']         = sanitize_text_field( $curr_val['deposit-meeting-date'] );
	}

	$metadata['group'] = array();
	$deposit_groups    = $curr_val['deposit-group'];
	if ( ! empty( $deposit_groups ) ) {
		foreach ( $deposit_groups as $group_id ) {
			$group                   = groups_get_group( array( 'group_id' => sanitize_text_field( $group_id ) ) );
			$metadata['group'][]     = $group->name;
			$metadata['group_ids'][] = $group_id;
		}
	}

	$metadata['subject'] = array();
	$deposit_subjects    = $curr_val['deposit-subject'];
	if ( ! empty( $deposit_subjects ) ) {
		foreach ( $deposit_subjects as $subject ) {
			$metadata['subject'][] = sanitize_text_field( stripslashes( $subject ) );
			// Subject ids will be set later.
		}
	}

	$metadata['keyword'] = array();
	$deposit_keywords    = $curr_val['deposit-keyword'];
	if ( ! empty( $deposit_keywords ) ) {
		foreach ( $deposit_keywords as $keyword ) {
			$metadata['keyword'][] = sanitize_text_field( stripslashes( $keyword ) );
			// Keyword ids will be set later.
		}
	}

	$metadata['type_of_resource'] = sanitize_text_field( $curr_val['deposit-resource-type'] );
	$metadata['language']         = sanitize_text_field( $curr_val['deposit-language'] );
	$metadata['notes']            = sanitize_text_field( stripslashes( $curr_val['deposit-notes-unchanged'] ) ); // Where do they go in MODS?
	$metadata['notes_unchanged']  = wp_kses(
		stripslashes( $curr_val['deposit-notes-unchanged'] ),
		array(
			'b'      => array(),
			'em'     => array(),
			'strong' => array(),
		)
	);
	$metadata['type_of_license']  = sanitize_text_field( $curr_val['deposit-license-type'] );
	$metadata['published']        = sanitize_text_field( $curr_val['deposit-published'] ); // Not stored in solr.
	if ( ! empty( $curr_val['deposit-publication-type'] ) ) {
		$metadata['publication-type'] = sanitize_text_field( $curr_val['deposit-publication-type'] ); // Not stored in solr.
	} else {
		$metadata['publication-type'] = 'none';
	}

	if ( 'book' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-book-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-book-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['edition'] = sanitize_text_field( $curr_val['deposit-book-edition'] );
		$metadata['volume']  = sanitize_text_field( $curr_val['deposit-book-volume'] );
		$metadata['isbn']    = sanitize_text_field( $curr_val['deposit-book-isbn'] );
		$metadata['doi']     = sanitize_text_field( $curr_val['deposit-book-doi'] );
	} elseif ( 'book-chapter' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-book-chapter-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-book-chapter-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['book_journal_title'] = sanitize_text_field( $curr_val['deposit-book-chapter-title'] );
		$metadata['book_author']        = sanitize_text_field( $curr_val['deposit-book-chapter-author'] );
		$metadata['chapter']            = sanitize_text_field( $curr_val['deposit-book-chapter-chapter'] );
		$metadata['start_page']         = sanitize_text_field( $curr_val['deposit-book-chapter-start-page'] );
		$metadata['end_page']           = sanitize_text_field( $curr_val['deposit-book-chapter-end-page'] );
		$metadata['isbn']               = sanitize_text_field( $curr_val['deposit-book-chapter-isbn'] );
		$metadata['doi']                = sanitize_text_field( $curr_val['deposit-book-chapter-doi'] );
	} elseif ( 'book-review' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-book-review-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-book-review-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['doi'] = sanitize_text_field( $curr_val['deposit-book-review-doi'] );
	} elseif ( 'book-section' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-book-section-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-book-section-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['book_journal_title'] = sanitize_text_field( $curr_val['deposit-book-section-title'] );
		$metadata['book_author']        = sanitize_text_field( $curr_val['deposit-book-section-author'] );
		$metadata['edition']            = sanitize_text_field( $curr_val['deposit-book-section-edition'] );
		$metadata['start_page']         = sanitize_text_field( $curr_val['deposit-book-section-start-page'] );
		$metadata['end_page']           = sanitize_text_field( $curr_val['deposit-book-section-end-page'] );
		$metadata['isbn']               = sanitize_text_field( $curr_val['deposit-book-section-isbn'] );
		$metadata['doi']                = sanitize_text_field( $curr_val['deposit-book-section-doi'] );
	} elseif ( 'journal-article' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-journal-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-journal-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['book_journal_title'] = sanitize_text_field( $curr_val['deposit-journal-title'] );
		$metadata['volume']             = sanitize_text_field( $curr_val['deposit-journal-volume'] );
		$metadata['issue']              = sanitize_text_field( $curr_val['deposit-journal-issue'] );
		$metadata['start_page']         = sanitize_text_field( $curr_val['deposit-journal-start-page'] );
		$metadata['end_page']           = sanitize_text_field( $curr_val['deposit-journal-end-page'] );
		$metadata['issn']               = sanitize_text_field( $curr_val['deposit-journal-issn'] );
		$metadata['doi']                = sanitize_text_field( $curr_val['deposit-journal-doi'] );
	} elseif ( 'magazine-section' == $metadata['publication-type'] ) {
		$metadata['date'] = sanitize_text_field( $curr_val['deposit-magazine-section-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['book_journal_title'] = sanitize_text_field( $curr_val['deposit-magazine-section-title'] );
		$metadata['volume']             = sanitize_text_field( $curr_val['deposit-magazine-section-volume'] );
		$metadata['start_page']         = sanitize_text_field( $curr_val['deposit-magazine-section-start-page'] );
		$metadata['end_page']           = sanitize_text_field( $curr_val['deposit-magazine-section-end-page'] );
		$metadata['url']                = sanitize_text_field( $curr_val['deposit-magazine-section-url'] );
	} elseif ( 'monograph' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-monograph-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-monograph-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['isbn'] = sanitize_text_field( $curr_val['deposit-monograph-isbn'] );
		$metadata['doi']  = sanitize_text_field( $curr_val['deposit-monograph-doi'] );
	} elseif ( 'newspaper-article' == $metadata['publication-type'] ) {
		$metadata['date'] = sanitize_text_field( $curr_val['deposit-newspaper-article-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['book_journal_title'] = sanitize_text_field( $curr_val['deposit-newspaper-article-title'] );
		$metadata['edition']            = sanitize_text_field( $curr_val['deposit-newspaper-article-edition'] );
		$metadata['volume']             = sanitize_text_field( $curr_val['deposit-newspaper-article-volume'] );
		$metadata['start_page']         = sanitize_text_field( $curr_val['deposit-newspaper-article-start-page'] );
		$metadata['end_page']           = sanitize_text_field( $curr_val['deposit-newspaper-article-end-page'] );
		$metadata['url']                = sanitize_text_field( $curr_val['deposit-newspaper-article-url'] );
	} elseif ( 'online-publication' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-online-publication-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-online-publication-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['book_journal_title'] = sanitize_text_field( $curr_val['deposit-online-publication-title'] );
		$metadata['edition']            = sanitize_text_field( $curr_val['deposit-online-publication-edition'] );
		$metadata['volume']             = sanitize_text_field( $curr_val['deposit-online-publication-volume'] );
		$metadata['url']                = sanitize_text_field( $curr_val['deposit-online-publication-url'] );
	} elseif ( 'podcast' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-podcast-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-podcast-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['volume'] = sanitize_text_field( $curr_val['deposit-podcast-volume'] );
		$metadata['url']    = sanitize_text_field( $curr_val['deposit-podcast-url'] );
	} elseif ( 'proceedings-article' == $metadata['publication-type'] ) {
		$metadata['publisher'] = sanitize_text_field( $curr_val['deposit-proceeding-publisher'] );
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-proceeding-publish-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
		$metadata['book_journal_title'] = sanitize_text_field( $curr_val['deposit-proceeding-title'] );
		$metadata['start_page']         = sanitize_text_field( $curr_val['deposit-proceeding-start-page'] );
		$metadata['end_page']           = sanitize_text_field( $curr_val['deposit-proceeding-end-page'] );
		$metadata['doi']                = sanitize_text_field( $curr_val['deposit-proceeding-doi'] );
	} elseif ( 'none' == $metadata['publication-type'] ) {
		$metadata['publisher'] = '';
		$metadata['date']      = sanitize_text_field( $curr_val['deposit-non-published-date'] );
		if ( ! empty( $metadata['date'] ) ) {
			$metadata['date_issued'] = get_year_issued( $metadata['date'] );
		} else {
			$metadata['date_issued'] = date( 'Y', strtotime( 'today' ) );
		}
	}

	$metadata['embargoed'] = sanitize_text_field( $curr_val['deposit-embargoed-flag'] );

	if ( 'yes' === $metadata['embargoed'] ) {
			$metadata['embargo_end_date'] = date( 'm/d/Y', strtotime( '+' . sanitize_text_field( $curr_val['deposit-embargo-length'] ) ) );
	}

			return $metadata;

}

/**
 * Get the year from the date entered.
 *
 * @param string $date Date entered
 * @return string Date in YYYY format
 */
function get_year_issued( $date_entered ) {

	// The strtotime function will handle a wide variety of entries. First address some cases it will not handle.
	$temp_date_entered = preg_replace(
		'~^(winter(?:/|)|spring(?:/|)|summer(?:/|)|fall(?:/|)|autumn(?:/|))+\s(\d{4})$~i',
		'Jan $2',
		$date_entered
	); // Custom publication date format.

	$temp_date_entered = preg_replace(
		'/^(\d{4})$/',
		'Jan $1',
		$temp_date_entered
	); // Workaround for when only YYYY is entered.

	$ambiguous_date = preg_match( '~^(\d{2})-(\d{2})-(\d{2}(?:\d{2})?)(?:\s.*?|)$~', $temp_date_entered, $matches );
	if ( 1 === $ambiguous_date ) { // Just deal with slashes.
			$temp_date_entered = sprintf( '%1$s/%2$s/%3$s', $matches[1], $matches[2], $matches[3] );
	}

	$ambiguous_date = preg_match( '~^(\d{2})/(\d{2})/(\d{2}(?:\d{2})?)(?:\s.*?|)$~', $temp_date_entered, $matches );
	if ( 1 === $ambiguous_date && $matches[1] > 12 ) { // European date in d/m/y format will fail for dd > 12.
		$temp_date_entered = sprintf( '%1$s/%2$s/%3$s', $matches[2], $matches[1], $matches[3] );
	}

	$date_value = strtotime( $temp_date_entered );

	if ( false === $date_value ) {
		return date( 'Y', strtotime( 'today' ) ); //TODO Real date edit message, kick back to user to fix. Meanwhile, this year is better than nothing.
	}

	return date( 'Y', $date_value );

}

/**
 * Format the xml used to create the DC datastream for the aggregator object.
 *
 * @param array $args Array of arguments.
 * @return WP_Error|string xml content
 * @see wp_parse_args()
 */
function create_aggregator_xml( $args ) {

	$defaults = array(
		'pid'     => '',
		'creator' => 'HumCORE',
		'title'   => 'Generic Content Aggregator',
		'type'    => 'InteractiveResource',
	);
	$params   = wp_parse_args( $args, $defaults );

	$pid     = $params['pid'];
	$creator = $params['creator'];
	$title   = $params['title'];
	$type    = $params['type'];

	if ( empty( $pid ) ) {
		return new WP_Error( 'missingArg', 'PID is missing.' );
	}

	return '<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
			xmlns:dc="http://purl.org/dc/elements/1.1/"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
		  <dc:identifier>' . $pid . '</dc:identifier>
		  <dc:creator>' . $creator . '</dc:creator>
		  <dc:title>' . $title . '</dc:title>
		  <dc:type>' . $type . '</dc:type>
		</oai_dc:dc>';

}

/**
 * Format the rdf used to create the RELS-EXT datastream for the aggregator object.
 *
 * @param array $args Array of arguments.
 * @return WP_Error|string rdf content
 * @see wp_parse_args()
 */
function create_aggregator_rdf( $args ) {

	$defaults = array(
		'pid'           => '',
		'collectionPid' => '',
		'isCollection'  => false,
		'fedoraModel'   => 'ContentAggregator',
	);
	$params   = wp_parse_args( $args, $defaults );

	$pid            = $params['pid'];
	$collection_pid = $params['collectionPid'];
	$is_collection  = $params['isCollection'];
	$fedora_model   = $params['fedoraModel'];

	if ( empty( $pid ) ) {
		return new WP_Error( 'missingArg', 'PID is missing.' );
	}

	$member_of_markup = '';
	if ( ! empty( $collection_pid ) ) {
		$member_of_markup = sprintf( '<pcdm:memberOf rdf:resource="info:fedora/%1$s"></pcdm:memberOf>', $collection_pid );
	}

	$is_collection_markup = '';
	if ( $is_collection ) {
		$is_collection_markup = '<isCollection xmlns="info:fedora/fedora-system:def/relations-external#">true</isCollection>';
	}

	return '<rdf:RDF xmlns:fedora-model="info:fedora/fedora-system:def/model#"
			xmlns:ore="http://www.openarchives.org/ore/terms/"
			xmlns:pcdm="http://pcdm.org/models#"
			xmlns:cc="http://creativecommons.org/ns#"
			xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
		  <rdf:Description rdf:about="info:fedora/' . $pid . '">
			<fedora-model:hasModel rdf:resource="info:fedora/ldpd:' . $fedora_model . '"></fedora-model:hasModel>
			<rdf:type rdf:resource="http://pcdm.org/models#Object"></rdf:type>
			' . $is_collection_markup . '
			' . $member_of_markup . '
			<cc:license rdf:resource="info:fedora/"></cc:license>
		   </rdf:Description>
		</rdf:RDF>';

}

/**
 * Format the xml used to create the DC datastream for the resource object.
 *
 * @param array $args Array of arguments.
 * @return WP_Error|string xml content
 * @see wp_parse_args()
 */
function create_resource_xml( $metadata, $filetype = '' ) {

	if ( empty( $metadata ) ) {
		return new WP_Error( 'missingArg', 'metadata is missing.' );
	}
	$pid = $metadata['pid'];
	if ( empty( $pid ) ) {
		return new WP_Error( 'missingArg', 'PID is missing.' );
	}
	$title        = humcore_cleanup_utf8( htmlspecialchars( $metadata['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
	$type         = humcore_cleanup_utf8( htmlspecialchars( $metadata['genre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
	$description  = humcore_cleanup_utf8( htmlspecialchars( $metadata['abstract'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
	$creator_list = '';
	foreach ( $metadata['authors'] as $author ) {
		if ( ( in_array( $author['role'], array( 'creator', 'author' ) ) ) && ! empty( $author['fullname'] ) ) {
				$creator_list .= '
                                  <dc:creator>' . $author['fullname'] . '</dc:creator>';
		}
	}

			$subject_list = '';
	foreach ( $metadata['subject'] as $subject ) {
			$subject_list .= '
                        <dc:subject>' . humcore_cleanup_utf8( htmlspecialchars( $subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) ) . '</dc:subject>';
	}
	if ( ! empty( $metadata['publisher'] ) ) {
		$publisher = '<dc:publisher>' . humcore_cleanup_utf8( htmlspecialchars( $metadata['publisher'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) ) . '</dc:publisher>';
	} else {
		$publisher = '';
	}
	if ( ! empty( $metadata['date_issued'] ) ) {
			$date = '
                        <dc:date encoding="w3cdtf">' . $metadata['date_issued'] . '</dc:date>';
	} else {
		$date = '';
	}

	return '<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
		  <dc:identifier>' . $pid . '</dc:identifier>
		  ' . $creator_list . '
		  ' . $date . '
		  <dc:title>' . $title . '</dc:title>
		  <dc:description>' . $description . '</dc:description>
		  ' . $subject_list . '
		  ' . $publisher . '
		  <dc:type>' . $type . '</dc:type>
		  <dc:format>' . $filetype . '</dc:format>
		</oai_dc:dc>';

}

/**
 * Format the rdf used to create the RELS-EXT datastream for the aggregator object.
 *
 * @param array $args Array of arguments.
 * @return WP_Error|string rdf content
 * @see wp_parse_args()
 */
function create_resource_rdf( $args ) {

	$defaults = array(
		'aggregatorPid' => '',
		'resourcePid'   => '',
		'collectionPid' => '',
	);
	$params   = wp_parse_args( $args, $defaults );

	$aggregator_pid    = $params['aggregatorPid'];
	$resource_pid      = $params['resourcePid'];
	$collection_pid    = $params['collectionPid'];
	$collection_markup = '';

	if ( empty( $aggregator_pid ) ) {
		return new WP_Error( 'missingArg', 'PID is missing.' );
	}

	if ( empty( $resource_pid ) ) {
		return new WP_Error( 'missingArg', 'PID is missing.' );
	}

	if ( ! empty( $collection_pid ) ) {
		$collection_markup = sprintf( '<pcdm:memberOf rdf:resource="info:fedora/%1$s"></pcdm:memberOf>', $collection_pid );
	}

	return '<rdf:RDF xmlns:fedora-model="info:fedora/fedora-system:def/model#"
			xmlns:dcmi="http://purl.org/dc/terms/"
			xmlns:pcdm="http://pcdm.org/models#"
			xmlns:rel="info:fedora/fedora-system:def/relations-external#"
			xmlns:cc="http://creativecommons.org/ns#"
			xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
		  <rdf:Description rdf:about="info:fedora/' . $resource_pid . '">
			<fedora-model:hasModel rdf:resource="info:fedora/ldpd:Resource"></fedora-model:hasModel>
			<rdf:type rdf:resource="http://pcdm.org/models#File"></rdf:type>
			<pcdm:memberOf rdf:resource="info:fedora/' . $aggregator_pid . '"></pcdm:memberOf>
			' . $collection_markup . '
			<cc:license rdf:resource="info:fedora/"></cc:license>
		  </rdf:Description>
		</rdf:RDF>';

}

/**
 * Format the foxml used to create Fedora aggregator and resource objects.
 *
 * @param array $args Array of arguments.
 * @return WP_Error|string foxml content
 * @see wp_parse_args()
 */
function create_foxml( $args ) {

	$defaults = array(
		'pid'        => '',
		'label'      => '',
		'xmlContent' => '',
		'state'      => 'Active',
		'rdfContent' => '',
	);
	$params   = wp_parse_args( $args, $defaults );

	$pid         = $params['pid'];
	$label       = $params['label'];
	$xml_content = $params['xmlContent'];
	$state       = $params['state'];
	$rdf_content = $params['rdfContent'];

	if ( empty( $pid ) ) {
		return new WP_Error( 'missingArg', 'PID is missing.' );
	}

	if ( empty( $xml_content ) ) {
		return new WP_Error( 'missingArg', 'XML string is missing.' );
	}

	if ( empty( $rdf_content ) ) {
		return new WP_Error( 'missingArg', 'RDF string is missing.' );
	}

	$output = '<?xml version="1.0" encoding="UTF-8"?>
		<foxml:digitalObject VERSION="1.1" PID="' . $pid . '"
			xmlns:foxml="info:fedora/fedora-system:def/foxml#"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="info:fedora/fedora-system:def/foxml# http://www.fedora.info/definitions/1/0/foxml1-1.xsd">
			<foxml:objectProperties>
				<foxml:property NAME="info:fedora/fedora-system:def/model#state" VALUE="' . $state . '"/>
				<foxml:property NAME="info:fedora/fedora-system:def/model#label" VALUE="' . $label . '"/>
			</foxml:objectProperties>
			<foxml:datastream ID="DC" STATE="A" CONTROL_GROUP="X" VERSIONABLE="true">
				<foxml:datastreamVersion ID="DC1.0" LABEL="Dublin Core Record for this object"
						CREATED="' . gmdate( 'Y-m-d\TH:i:s\Z' ) . '" MIMETYPE="text/xml"
						FORMAT_URI="http://www.openarchives.org/OAI/2.0/oai_dc/" SIZE="' . strlen( $xml_content ) . '">
					<foxml:xmlContent>' . $xml_content . '</foxml:xmlContent>
				</foxml:datastreamVersion>
			</foxml:datastream>
			<foxml:datastream ID="RELS-EXT" STATE="A" CONTROL_GROUP="X" VERSIONABLE="true">
				<foxml:datastreamVersion ID="RELS-EXT1.0" LABEL="RDF Statements about this object"
						CREATED="' . gmdate( 'Y-m-d\TH:i:s\Z' ) . '" MIMETYPE="application/rdf+xml"
						FORMAT_URI="info:fedora/fedora-system:FedoraRELSExt-1.0" SIZE="' . strlen( $rdf_content ) . '">
					<foxml:xmlContent>' . $rdf_content . '</foxml:xmlContent>
				</foxml:datastreamVersion>
			</foxml:datastream>
		</foxml:digitalObject>';

	$dom                     = new DOMDocument;
	$dom->preserveWhiteSpace = false; // @codingStandardsIgnoreLine camelCase
	if ( false === $dom->loadXML( $output ) ) {
		humcore_write_error_log( 'error', '*****HumCORE Error - bad xml content*****' . var_export( $pid, true ) );
	}
	$dom->formatOutput = true; // @codingStandardsIgnoreLine camelCase
	return $dom->saveXML();

}

/**
 * Format the xml used to create the CONTENT datastream for the MODS metadata object.
 *
 * @param array $metadata
 * @return WP_Error|string mods xml content
 */
function create_mods_xml( $metadata ) {

	/**
	 * Format MODS xml fragment for one or more authors.
	 */
	$author_mods = '';
	foreach ( $metadata['authors'] as $author ) {

		if ( in_array( $author['role'], array( 'creator', 'author' ) ) ) {
			if ( 'creator' === $author['role'] ) {
				$author_mods .= '
				<name type="corporate">';
			} else {
				if ( ! empty( $author['uni'] ) ) {
					$author_mods .= '
					<name type="personal" ID="' . $author['uni'] . '">';
				} else {
					$author_mods .= '
					<name type="personal">';
				}
			}

			if ( ( 'creator' !== $author['role'] ) && ( ! empty( $author['family'] ) || ! empty( $author['given'] ) ) ) {
				$author_mods .= '
				  <namePart type="family">' . $author['family'] . '</namePart>
				  <namePart type="given">' . $author['given'] . '</namePart>';
				//          } else if ( 'creator' !== $author['role'] ) {
			} else {
				$author_mods .= '
				<namePart>' . $author['fullname'] . '</namePart>';
			}

			if ( 'creator' === $author['role'] ) {
				$author_mods .= '
					<role>
						<roleTerm type="text">creator</roleTerm>
					</role>';
			} else {
				$author_mods .= '
					<role>
						<roleTerm type="text">' . $author['role'] . '</roleTerm>
					</role>';
			}

			if ( ! empty( $author['affiliation'] ) ) {
				$author_mods .= '
				  <affiliation>' . htmlspecialchars( $author['affiliation'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</affiliation>';
			}

			$author_mods .= '
				</name>';

		}
	}

	/**
	 * Format MODS xml fragment for organization affiliation.
	 */
	$org_mods = '';
	if ( ! empty( $metadata['genre'] ) && in_array( $metadata['genre'], array( 'Dissertation', 'Technical report', 'Thesis', 'White paper' ) ) &&
		! empty( $metadata['institution'] ) ) {
		$org_mods .= '
				<name type="corporate">
				  <namePart>
					' . htmlspecialchars( $metadata['institution'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '
				  </namePart>
				  <role>
					<roleTerm type="text">originator</roleTerm>
				  </role>
				</name>';
	}

	/**
	 * Format MODS xml fragment for date issued.
	 */
	$date_issued_mods = '';
	if ( ! empty( $metadata['date_issued'] ) ) {
		$date_issued_mods = '
			<originInfo>
				<dateIssued encoding="w3cdtf" keyDate="yes">' . $metadata['date_issued'] . '</dateIssued>
			</originInfo>';
	}

	/**
	 * Format MODS xml fragment for resource type.
	 */
	$resource_type_mods = '';
	if ( ! empty( $metadata['type_of_resource'] ) ) {
		$resource_type_mods = '
			<typeOfResource>' . $metadata['type_of_resource'] . '</typeOfResource>';
	}

	/**
	 * Format MODS xml fragment for language.
	 */
	$language_mods = '';
	if ( ! empty( $metadata['language'] ) ) {
		$term          = wpmn_get_term_by( 'name', $metadata['language'], 'humcore_deposit_language' );
		$language_mods = '
			<language>
                                <languageTerm authority="iso639-3" >' . $term->slug . '</languageTerm>
                        </language>';
	}

	/**
	 * Format MODS xml fragment for genre.
	 */
	$genre_mods = '';
	if ( ! empty( $metadata['genre'] ) ) {
		$genre_mods = '
			<genre>' . htmlspecialchars( $metadata['genre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</genre>';
	}

	/**
	 * Format MODS xml fragment for one or more subjects.
	 */
	$full_subject_list = $metadata['subject'];
	$subject_mods      = '';
	foreach ( $full_subject_list as $subject ) {
		$subject_xml = '';
		[$fast_id, $fast_subject, $fast_facet] = explode(":", $subject);
		$fast_subject = htmlspecialchars( $fast_subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
		switch ($fast_facet) {
			case "Topic":
			case "Event":
				$subject_xml = "<topic>{$fast_subject}</topic>";
				break;
			case "Form/Genre":
				$subject_xml = "<form>{$fast_subject}</form>";
				break;
			case "Period": // same as chronological?
				$subject_xml = "<temporal>{$fast_subject}</temporal>";
				break;
			case "Personal Name":
				$subject_xml = "<name type=\"personal\"><namePart>{$fast_subject}</namePart></name>";
				break;
			case "Corporate Name":
				$subject_xml = "<name type=\"corporate\"><namePart>{$fast_subject}</namePart></name>";
				break;
			case "Meeting":
				$subject_xml = "<name type=\"conference\"><namePart>{$fast_subject}</namePart></name>";
				break;
			case "Uniform Title":
				$subject_xml = "<titleInfo type=\"uniform\"><title>{$fast_subject}</title></titleInfo>";
				break;
			case "Geographic":
				$subject_xml = "<geographic>{$fast_subject}</geographic>";
				break;
			default:
				$subject_xml = "<topic>{$fast_subject}</topic>";
				break;
		}
		// the outer <subject> tag is the same for all facets
		$subject_xml = "<subject authority=\"fast\" authorityURI=\"http://id.worldcat.org/fast\" valueURI=\"http://id.worldcat.org/fast/{$fast_id}\">" .
		"{$subject_xml}</subject>";
		//
		// concatenate all the subjects
		$subject_mods .= $subject_xml;			
	}

	$related_item_mods = '';
	if ( 'journal-article' == $metadata['publication-type'] ) {
		$related_item_mods = '
				<relatedItem type="host">
					<titleInfo>';
		if ( ! empty( $metadata['book_journal_title'] ) ) {
			$related_item_mods .= '
						<title>' . htmlspecialchars( $metadata['book_journal_title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</title>';
		} else {
			$related_item_mods .= '
						<title/>';
		}
		$related_item_mods .= '
					</titleInfo>';
		if ( ! empty( $metadata['publisher'] ) ) {
			$related_item_mods .= '
					<originInfo>
						<publisher>' . htmlspecialchars( $metadata['publisher'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</publisher>';
			if ( ! empty( $metadata['date_issued'] ) ) {
				$related_item_mods .= '
						<dateIssued encoding="w3cdtf">' . $metadata['date_issued'] . '</dateIssued>';
			}
			$related_item_mods .= '
					</originInfo>';
		}
		$related_item_mods .= '
					<part>';
		if ( ! empty( $metadata['volume'] ) ) {
			$related_item_mods .= '
						<detail type="volume">
							<number>' . $metadata['volume'] . '</number>
						</detail>';
		}
		if ( ! empty( $metadata['issue'] ) ) {
			$related_item_mods .= '
						<detail type="issue">
							<number>' . $metadata['issue'] . '</number>
						</detail>';
		}
		if ( ! empty( $metadata['start_page'] ) ) {
			$related_item_mods .= '
						<extent unit="page">
							<start>' . $metadata['start_page'] . '</start>
							<end>' . $metadata['end_page'] . '</end>
						</extent>';
		}
		if ( ! empty( $metadata['date'] ) ) {
			$related_item_mods .= '
						<date>' . $metadata['date'] . '</date>';
		}
		$related_item_mods .= '
					</part>';
		if ( ! empty( $metadata['doi'] ) ) {
			$related_item_mods .= '
					<identifier type="doi">' . $metadata['doi'] . '</identifier>';
		}
		if ( ! empty( $metadata['issn'] ) ) {
			$related_item_mods .= '
					<identifier type="issn">' . $metadata['issn'] . '</identifier>';
		}
		$related_item_mods .= '
				</relatedItem>';
	} elseif ( 'book-chapter' == $metadata['publication-type'] ) {
		$related_item_mods = '
				<relatedItem type="host">
					<titleInfo>';
		if ( ! empty( $metadata['book_journal_title'] ) ) {
			$related_item_mods .= '
						<title>' . htmlspecialchars( $metadata['book_journal_title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</title>';
		} else {
			$related_item_mods .= '
						<title/>';
		}
		$related_item_mods .= '
					</titleInfo>';
		if ( ! empty( $metadata['book_author'] ) ) {
			$related_item_mods .= '
						<name type="personal">
						<namePart>' . $metadata['book_author'] . '</namePart>
						<role>
						<roleTerm type="text">editor</roleTerm>
						</role>
					</name>';
		}
		if ( ! empty( $metadata['publisher'] ) ) {
			$related_item_mods .= '
					<originInfo>
						<publisher>' . htmlspecialchars( $metadata['publisher'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</publisher>';
			if ( ! empty( $metadata['date_issued'] ) ) {
				$related_item_mods .= '
						<dateIssued encoding="w3cdtf">' . $metadata['date_issued'] . '</dateIssued>';
			}
			$related_item_mods .= '
					</originInfo>';
		}
		$related_item_mods .= '
					<part>';
		if ( ! empty( $metadata['chapter'] ) ) {
			$related_item_mods .= '
						<detail type="chapter">
							<number>' . $metadata['chapter'] . '</number>
						</detail>';
		}
		if ( ! empty( $metadata['start_page'] ) ) {
			$related_item_mods .= '
						<extent unit="page">
							<start>' . $metadata['start_page'] . '</start>
							<end>' . $metadata['end_page'] . '</end>
						</extent>';
		}
		if ( ! empty( $metadata['date'] ) ) {
			$related_item_mods .= '
						<date>' . $metadata['date'] . '</date>';
		}
		$related_item_mods .= '
					</part>';
		if ( ! empty( $metadata['doi'] ) ) {
			$related_item_mods .= '
					<identifier type="doi">' . $metadata['doi'] . '</identifier>';
		}
		if ( ! empty( $metadata['isbn'] ) ) {
			$related_item_mods .= '
					<identifier type="isbn">' . $metadata['isbn'] . '</identifier>';
		}
		$related_item_mods .= '
				</relatedItem>';
	} elseif ( ! empty( $metadata['genre'] ) && ( 'Conference proceeding' == $metadata['genre'] || 'Conference paper' == $metadata['genre'] || 'Conference poster' == $metadata['genre'] ) ) {
		$related_item_mods = '
				<relatedItem type="host">
					<titleInfo>';
		if ( ! empty( $metadata['conference_title'] ) ) {
			$related_item_mods .= '
						<title>' . htmlspecialchars( $metadata['conference_title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</title>';
		} else {
			$related_item_mods .= '
						<title/>';
		}
		$related_item_mods .= '
					</titleInfo>';
		if ( ! empty( $metadata['publisher'] ) ) {
			$related_item_mods .= '
					<originInfo>
						<publisher>' . htmlspecialchars( $metadata['publisher'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</publisher>';
			if ( ! empty( $metadata['date_issued'] ) ) {
				$related_item_mods .= '
						<dateIssued encoding="w3cdtf">' . $metadata['date_issued'] . '</dateIssued>';
			}
			$related_item_mods .= '
					</originInfo>';
		}
		$related_item_mods .= '
				</relatedItem>';
	}

	/**
	 * Format the xml used to create the CONTENT datastream for the MODS metadata object.
	 */
	$metadata_mods = '<mods xmlns="http://www.loc.gov/mods/v3"
		  xmlns:xlink="http://www.w3.org/1999/xlink"
		  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		  xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-4.xsd">
			<titleInfo>
				<title>' . htmlspecialchars( $metadata['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</title>
			</titleInfo>
			' . $author_mods . '
			' . $org_mods . '
			' . $resource_type_mods . '
			' . $genre_mods . '
			' . $date_issued_mods . '
			' . $language_mods . '
			<abstract>' . htmlspecialchars( $metadata['abstract'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) . '</abstract>
			' . $subject_mods . '
			' . $related_item_mods . '
			<recordInfo>
				<recordCreationDate encoding="w3cdtf">' . date( 'Y-m-d H:i:s O' ) . '</recordCreationDate>
				<languageOfCataloging>
					<languageTerm authority="iso639-3">eng</languageTerm>
				</languageOfCataloging>
			</recordInfo>
		</mods>';

	return $metadata_mods;

}

/**
 * Prepare metadata for the edit screen from existing post metadata.
 *
 * @param array $curr_val array of existing metadata entries.
 * @return array metadata content
 */
function humcore_prepare_edit_page_metadata( $curr_val ) {

	$metadata['submitter'] = $curr_val['submitter'];
	$deposit_author_user   = get_user_by( 'ID', $curr_val['submitter'] );

	$metadata['deposit-title-unchanged'] = $curr_val['title_unchanged'];
	if ( empty( $metadata['deposit-title-unchanged'] ) ) {
		$metadata['deposit-title-unchanged'] = $curr_val['title'];
	}
	$metadata['deposit-abstract-unchanged'] = $curr_val['abstract_unchanged'];
	if ( empty( $metadata['deposit-abstract-unchanged'] ) ) {
		$metadata['deposit-abstract-unchanged'] = $curr_val['abstract'];
	}
	$metadata['deposit-notes-unchanged'] = $curr_val['notes_unchanged'];
	if ( empty( $metadata['deposit-notes-unchanged'] ) ) {
		$metadata['deposit-notes-unchanged'] = $curr_val['notes'];
	}
	$metadata['deposit-genre']          = $curr_val['genre'];
	$metadata['deposit-for-others-flag'] = $curr_val['deposit_for_others'];
	$metadata['deposit-on-behalf-flag'] = $curr_val['committee_deposit'];
	if ( ! empty( $curr_val['committee_id'] ) ) {
		$metadata['deposit-committee'] = $curr_val['committee_id'];
	} else {
		$metadata['deposit-committee'] = '';
	}

	if ( 'yes' === $curr_val['committee_deposit'] ) {
		$committee                = groups_get_group( array( 'group_id' => $curr_val['committee_id'] ) );
		$metadata['organization'] = strtoupper( $society_id );
		$metadata['authors'][]    = array(
			'fullname'    => $committee->name,
			'given'       => '',
			'family'      => '',
			'uni'         => $committee->slug,
			'role'        => 'creator',
			'affiliation' => strtoupper( $society_id ),
		);
	}

	$author_count = 0;
	if ( ! empty( $curr_val['authors'] ) ) {
		foreach ( $curr_val['authors'] as $author ) {
			if ( $deposit_author_user->user_login === $author['uni'] && 'submitter' !== $author['role'] ) {
				$metadata['deposit-author-first-name'] = $author['given'];
				$metadata['deposit-author-last-name']  = $author['family'];
				$metadata['deposit-author-role']       = $author['role'];
				$metadata['deposit-author-uni']        = $author['uni'];
			} elseif ( 'submitter' !== $author['role'] ) {
				$metadata['deposit-other-authors-first-name'][ $author_count ] = $author['given'];
				$metadata['deposit-other-authors-last-name'][ $author_count ]  = $author['family'];
				$metadata['deposit-other-authors-role'][ $author_count ]       = $author['role'];
				$metadata['deposit-other-authors-uni'][ $author_count ]        = $author['uni'];
				$author_count++;
			}
		}
	}
	$metadata['deposit-institution']             = $curr_val['institution'];
	$metadata['deposit-conference-title']        = $curr_val['conference_title'];
	$metadata['deposit-conference-organization'] = $curr_val['conference_organization'];
	$metadata['deposit-conference-location']     = $curr_val['conference_location'];
	$metadata['deposit-conference-date']         = $curr_val['conference_date'];
	$metadata['deposit-meeting-title']           = $curr_val['meeting_title'];
	$metadata['deposit-meeting-organization']    = $curr_val['meeting_organization'];
	$metadata['deposit-meeting-location']        = $curr_val['meeting_location'];
	$metadata['deposit-meeting-date']            = $curr_val['meeting_date'];
	if ( ! empty( $curr_val['group_ids'] ) ) {
		foreach ( $curr_val['group_ids'] as $group_id ) {
			$metadata['deposit-group'][] = $group_id;
		}
	}
	//use ids to remake list
	if ( ! empty( $curr_val['subject'] ) ) {
		foreach ( $curr_val['subject'] as $subject ) {
			$metadata['deposit-subject'][] = $subject;
		}
	}
	//use ids to remake list
	if ( ! empty( $curr_val['keyword'] ) ) {
		foreach ( $curr_val['keyword'] as $keyword ) {
			$metadata['deposit-keyword'][] = $keyword;
		}
	}
	$metadata['deposit-resource-type']    = $curr_val['type_of_resource'];
	$metadata['deposit-language']         = $curr_val['language'];
	$metadata['deposit-license-type']     = $curr_val['type_of_license'];
	$metadata['deposit-published']        = $curr_val['published'];
	$metadata['deposit-publication-type'] = $curr_val['publication-type'];

	if ( 'book' == $curr_val['publication-type'] ) {
		$metadata['deposit-book-publisher']    = $curr_val['publisher'];
		$metadata['deposit-book-publish-date'] = $curr_val['date'];
		$metadata['deposit-book-edition']      = $curr_val['edition'];
		$metadata['deposit-book-volume']       = $curr_val['volume'];
		$metadata['deposit-book-isbn']         = $curr_val['isbn'];
		$metadata['deposit-book-doi']          = $curr_val['doi'];
	} elseif ( 'book-chapter' == $curr_val['publication-type'] ) {
		$metadata['deposit-book-chapter-publisher']    = $curr_val['publisher'];
		$metadata['deposit-book-chapter-publish-date'] = $curr_val['date'];
		$metadata['deposit-book-chapter-title']        = $curr_val['book_journal_title'];
		$metadata['deposit-book-chapter-author']       = $curr_val['book_author'];
		$metadata['deposit-book-chapter-chapter']      = $curr_val['chapter'];
		$metadata['deposit-book-chapter-start-page']   = $curr_val['start_page'];
		$metadata['deposit-book-chapter-end-page']     = $curr_val['end_page'];
		$metadata['deposit-book-chapter-isbn']         = $curr_val['isbn'];
		$metadata['deposit-book-chapter-doi']          = $curr_val['doi'];
	} elseif ( 'book-review' == $curr_val['publication-type'] ) {
		$metadata['deposit-book-review-publisher']    = $curr_val['publisher'];
		$metadata['deposit-book-review-publish-date'] = $curr_val['date'];
		$metadata['deposit-book-chapter-isbn']        = $curr_val['isbn'];
		$metadata['deposit-book-review-doi']          = $curr_val['doi'];
	} elseif ( 'book-section' == $curr_val['publication-type'] ) {
		$metadata['deposit-book-section-publisher']    = $curr_val['publisher'];
		$metadata['deposit-book-section-publish-date'] = $curr_val['date'];
		$metadata['deposit-book-section-title']        = $curr_val['book_journal_title'];
		$metadata['deposit-book-section-author']       = $curr_val['book_author'];
		$metadata['deposit-book-section-edition']      = $curr_val['edition'];
		$metadata['deposit-book-section-start-page']   = $curr_val['start_page'];
		$metadata['deposit-book-section-end-page']     = $curr_val['end_page'];
		$metadata['deposit-book-section-isbn']         = $curr_val['isbn'];
		$metadata['deposit-book-section-doi']          = $curr_val['doi'];
	} elseif ( 'journal-article' == $curr_val['publication-type'] ) {
		$metadata['deposit-journal-publisher']    = $curr_val['publisher'];
		$metadata['deposit-journal-publish-date'] = $curr_val['date'];
		$metadata['deposit-journal-title']        = $curr_val['book_journal_title'];
		$metadata['deposit-journal-author']       = $curr_val['book_author'];
		$metadata['deposit-journal-volume']       = $curr_val['volume'];
		$metadata['deposit-journal-issue']        = $curr_val['issue'];
		$metadata['deposit-journal-start-page']   = $curr_val['start_page'];
		$metadata['deposit-journal-end-page']     = $curr_val['end_page'];
		$metadata['deposit-journal-issn']         = $curr_val['issn'];
		$metadata['deposit-journal-doi']          = $curr_val['doi'];
	} elseif ( 'magazine-section' == $curr_val['publication-type'] ) {
		$metadata['deposit-magazine-section-publish-date'] = $curr_val['date'];
		$metadata['deposit-magazine-section-title']        = $curr_val['book_journal_title'];
		$metadata['deposit-magazine-section-volume']       = $curr_val['volume'];
		$metadata['deposit-magazine-section-start-page']   = $curr_val['start_page'];
		$metadata['deposit-magazine-section-end-page']     = $curr_val['end_page'];
		$metadata['deposit-magazine-section-url']          = $curr_val['url'];
	} elseif ( 'monograph' == $curr_val['publication-type'] ) {
		$metadata['deposit-monograph-publisher']    = $curr_val['publisher'];
		$metadata['deposit-monograph-publish-date'] = $curr_val['date'];
		$metadata['deposit-monograph-isbn']         = $curr_val['isbn'];
		$metadata['deposit-monograph-doi']          = $curr_val['doi'];
	} elseif ( 'newspaper-article' == $curr_val['publication-type'] ) {
		$metadata['deposit-newspaper-article-publish-date'] = $curr_val['date'];
		$metadata['deposit-newspaper-article-title']        = $curr_val['book_journal_title'];
		$metadata['deposit-newspaper-article-edition']      = $curr_val['edition'];
		$metadata['deposit-newspaper-article-volume']       = $curr_val['volume'];
		$metadata['deposit-newspaper-article-start-page']   = $curr_val['start_page'];
		$metadata['deposit-newspaper-article-end-page']     = $curr_val['end_page'];
		$metadata['deposit-newspaper-article-url']          = $curr_val['url'];
	} elseif ( 'online-publication' == $curr_val['publication-type'] ) {
		$metadata['deposit-online-publication-publisher']    = $curr_val['publisher'];
		$metadata['deposit-online-publication-publish-date'] = $curr_val['date'];
		$metadata['deposit-online-publication-title']        = $curr_val['book_journal_title'];
		$metadata['deposit-online-publication-edition']      = $curr_val['edition'];
		$metadata['deposit-online-publication-volume']       = $curr_val['volume'];
		$metadata['deposit-online-publication-url']          = $curr_val['url'];
	} elseif ( 'podcast' == $curr_val['publication-type'] ) {
		$metadata['deposit-podcast-publisher']    = $curr_val['publisher'];
		$metadata['deposit-podcast-publish-date'] = $curr_val['date'];
		$metadata['deposit-podcast-volume']       = $curr_val['volume'];
		$metadata['deposit-podcast-url']          = $curr_val['url'];
	} elseif ( 'proceedings-article' == $curr_val['publication-type'] ) {
		$metadata['deposit-proceeding-publisher']    = $curr_val['publisher'];
		$metadata['deposit-proceeding-publish-date'] = $curr_val['date'];
		$metadata['deposit-proceeding-title']        = $curr_val['book_journal_title'];
		$metadata['deposit-proceeding-start-page']   = $curr_val['start_page'];
		$metadata['deposit-proceeding-end-page']     = $curr_val['end_page'];
		$metadata['deposit-proceeding-doi']          = $curr_val['doi'];
	} elseif ( 'none' == $curr_val['publication-type'] ) {
		$metadata['deposit-non-published-date'] = $curr_val['date'];
	}
	$metadata['deposit-embargoed-flag'] = $curr_val['embargoed'];
	//calc embargo length from $curr_val['embargo_end_date'];
	if ( 'yes' === $curr_val['embargoed'] ) {
		$deposit_date     = new DateTime( $curr_val['record_creation_date'] );
		$embargo_end_date = new DateTime( $curr_val['embargo_end_date'] );

		$calculated_embargo_length = sprintf(
			'%s months',
			$deposit_date->diff( $embargo_end_date )->m + ( $deposit_date->diff( $embargo_end_date )->y * 12 ) + 1
		);
	}
	$metadata['deposit-embargo-length'] = $calculated_embargo_length;
	$record_location                    = explode( '-', $curr_val['record_identifier'] );
	// handle legacy MLA Commons value
	if ( $record_location[0] === $curr_val['record_identifier'] ) {
		$record_location[0] = '1';
		$record_location[1] = $curr_val['record_identifier'];
	}
	$metadata['deposit_blog_id'] = $record_location[0];
	$metadata['deposit_post_id'] = $record_location[1];

	return $metadata;

}

/**
 * 
 * OBSOLETE: NO loger used after switching to FAST subjects
 * 
 * Reclassify subjects and keywords. Subjects must be known, keywords should not duplicate a subject.
 *
 * @param array $metadata
 * @return array $metadata
 */
function humcore_reclassify_subjects_and_keywords( $metadata ) {

	$metadata['subject'] = array_unique( $metadata['subject'] );
	$metadata['keyword'] = array_unique( $metadata['keyword'] );

	$current_subjects = $metadata['subject'];
	$current_keywords = $metadata['keyword'];

	/**
	 * Move any unknown subjects to keywords.
	 */
	if ( ! empty( $current_subjects ) ) {
		foreach ( $current_subjects as $subject ) {
			$term_key = wpmn_term_exists( $subject, 'humcore_deposit_subject' );
			if ( empty( $term_key ) ) {
				$unknown_subject_key = array_search( $subject, $metadata['subject'] );
				if ( false !== $unknown_subject_key ) {
					unset( $metadata['subject'][ $unknown_subject_key ] );
				}
				if ( ! in_array( strtolower( $subject ), array_map( 'strtolower', $metadata['keyword'] ) ) ) {
					$metadata['keyword'][] = $subject;
				}
			}
		}
	}

	/**
	 * Move any keywords found in subjects to subjects.
	 */
	if ( ! empty( $current_keywords ) ) {
		foreach ( $current_keywords as $keyword ) {
			$term_key = wpmn_term_exists( $keyword, 'humcore_deposit_subject' );
			if ( ! empty( $term_key ) ) {
				$term              = wpmn_get_term( $term_key['term_id'], 'humcore_deposit_subject' );
				$known_subject_key = array_search( $keyword, $metadata['keyword'] );
				if ( false !== $known_subject_key ) {
					unset( $metadata['keyword'][ $known_subject_key ] );
				}
				if ( ! in_array( strtolower( $term->name ), array_map( 'strtolower', $metadata['subject'] ) ) ) {
					$metadata['subject'][] = $term->name;
				}
			}
		}
	}

	return $metadata;

}

/**
 * Format and ingest the foxml used to create a Fedora collection object.
 * Really only needed once per install.
 *
 * Example usage:
 * $c_status = create_collection_object();
 * var_export( $c_status, true );
 *
 * @global object $fedora_api {@link Humcore_Deposit_Fedora_Api}
 * @return WP_Error|string status
 * @see wp_parse_args()
 */
function create_collection_object() {

	global $fedora_api;

	$next_pids = $fedora_api->get_next_pid(
		array(
			'numPIDs'   => '1',
			'namespace' => $fedora_api->namespace . 'collection',
		)
	);
	if ( is_wp_error( $next_pids ) ) {
		echo 'Error - next_pids : ' . esc_html( $next_pids->get_error_code() ) . '-' . esc_html( $next_pids->get_error_message() );
		return $next_pids;
	}

	$collection_xml = create_aggregator_xml(
		array(
			'pid'   => $next_pids[0],
			'title' => 'Collection parent object for ' . $fedora_api->namespace,
			'type'  => 'Collection',
		)
	);

	$collection_rdf = create_aggregator_rdf(
		array(
			'pid'           => $next_pids[0],
			'collectionPid' => $fedora_api->collection_pid,
			'isCollection'  => true,
			'fedoraModel'   => 'BagAggregator',
		)
	);

	$collection_foxml = create_foxml(
		array(
			'pid'        => $next_pids[0],
			'label'      => '',
			'xmlContent' => $collection_xml,
			'state'      => 'Active',
			'rdfContent' => $collection_rdf,
		)
	);

	$c_ingest = $fedora_api->ingest( array( 'xmlContent' => $collection_foxml ) );
	if ( is_wp_error( $c_ingest ) ) {
		echo 'Error - c_ingest : ' . esc_html( $c_ingest->get_error_message() );
		return $c_ingest;
	}

	echo '<br />', __( 'Object Created: ', 'humcore_domain' ), date( 'Y-m-d H:i:s' ), var_export( $c_ingest, true );
	return $c_ingest;

}
