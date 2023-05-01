<?php
/**
 * Plugin Name: BuddyPress Docs Minor Edit
 * Description: Adds a "minor edit" checkbox to BuddyPress Docs which allows users to stop an activity item from being posted.
 */

class BuddyPressDocsMinorEdit {

	const CHECKBOX_NAME = 'doc-minor-edit';

	function __construct() {
		add_action( 'bp_docs_doc_saved', [ $this, 'prevent_activity' ], 5 );
		add_action( 'bp_docs_after_doc_edit_content', [ $this, 'add_checkbox' ] );
	}

	function prevent_activity() {
		//global $bp_docs;
		if ( isset( $_POST[ self::CHECKBOX_NAME ] ) ) {
			remove_action( 'bp_docs_doc_saved', 'bp_docs_post_activity' );
		}
	}

	function add_checkbox() {
		echo '<input name="' . self::CHECKBOX_NAME . '" type="checkbox" value="on" /> This is a minor edit (notifications will not be sent to subscribed group members)';
	}

}

new BuddyPressDocsMinorEdit;
