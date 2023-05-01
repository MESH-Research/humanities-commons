<?php
// Exit if accessed directly
if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * @since version 0.5
 * containes functions previous on index.php
 */

/**
 * Registers the plugin's template directory.
 *
 * @since 1.12
 */
function bp_group_documents_register_template_stack() {
	bp_register_template_stack( 'bp_group_documents_template_directory', 20 );
}
add_action( 'bp_actions', 'bp_group_documents_register_template_stack', 0 );

/**
 * Returns the directory containing the default templates for the plugin.
 *
 * @since 1.12
 *
 * @return string
 */
function bp_group_documents_template_directory() {
	return WP_PLUGIN_DIR . '/' . BP_GROUP_DOCUMENTS_DIR . '/templates';
}

/**
 * bp_group_documents_display()
 *
 * Loads the template part for the primary group display.
 *
 * version 2.0 7/3/2013 lenasterg
 */
function bp_group_documents_display() {
	bp_get_template_part( 'groups/single/documents' );
}

/**
 *
 * @version 2.0, 13/5/2013, lenasterg
 */
function bp_group_documents_display_header() {
    $nav_page_name = get_option('bp_group_documents_nav_page_name');

    $name = !empty($nav_page_name) ? $nav_page_name : __('Documents' , 'bp-group-documents');
    _e('Group') . ' ' . $name;
}

/**
 *
 */
function bp_group_documents_display_title() {
    echo get_option('bp_group_documents_nav_page_name') . ' ' . __('List' , 'bp-group-documents');
}

    /*     * ***********************************************************************
     * **********************EVERYTHING ELSE************************************
     * *********************************************************************** */

    /**
     * bp_group_documents_delete()
     *
     * After perfoming several validation checks, deletes both the uploaded
     * file and the reference in the database
     */
    function bp_group_documents_delete($id) {
        //check nonce
        if ( !wp_verify_nonce($_REQUEST['_wpnonce'] , 'group-documents-delete-link') ) {
            bp_core_add_message(__('There was a security problem' , 'bp-group-documents') , 'error');
            return false;
        }
        if ( !ctype_digit($id) ) {
            bp_core_add_message(__('The item to delete could not be found' , 'bp-group-documents') , 'error');
            return false;
        }



        $document = new BP_Group_Documents($id);
        if ( $document->current_user_can('delete') ) {
            if ( $document->delete() ) {
                do_action('bp_group_documents_delete_success' , $document);
                return true;
            }
        }
        return false;
    }

    /**
     * bp_group_documents_check_ext()
     *
     * checks whether the passed filename ends in an extension
     * that is allowed by the site admin
     */
    function bp_group_documents_check_ext($filename) {

        if ( !$filename ) {
            return false;
        }

        $valid_formats_string = get_option('bp_group_documents_valid_file_formats');
        $valid_formats_array = explode(',' , $valid_formats_string);

        $extension1 = substr($filename , (strrpos($filename , ".") + 1));
        $extension = strtolower($extension1);

        if ( in_array($extension , $valid_formats_array) ) {
            return true;
        }
        return false;
    }

    /**
     * get_file_size()
     *
     * returns a human-readable file-size for the passed file
     * adapted from a function in the PHP manual comments
     */
    function get_file_size($document , $precision = 1) {

        $units = array('b' , 'k' , 'm' , 'g');

        $bytes1 = file_exists($document->get_path(1)) ? filesize($document->get_path(1)) : 0;
        $bytes = max($bytes1 , 0);
        $pow1 = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow1 , count($units) - 1);

        $bytes /= pow(1024 , $pow);

        return round($bytes , $precision) . $units[$pow];
    }

    /**
     * return_bytes()
     *
     * taken from the PHP manual examples.  Returns the number of bites
     * when given an abrevition (eg, max_upload_size)
	 * @version 2.0 fix 7.2 error
     */
    function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val = 1024;
            case 'm':
                $val =(float)$val* 1024;
            case 'k':
                $val = (float)$val* 1024;
        }

        return $val;
    }

    /**
     * bp_group_documents_remove_data()
     *
     * Cleans out both the files and the database records when a group is deleted
     */
    function bp_group_documents_remove_data($group_id) {

        $results = BP_Group_Documents::get_list_by_group($group_id);
        if ( count($results) >= 1 ) {
            foreach ( $results as $document_params ) {
                $document = new BP_Group_Documents($document_params['id'] , $document_params);
                $document->delete();
                do_action('bp_group_documents_delete_with_group' , $document);
            }
        }
    }

    add_action('groups_group_deleted' , 'bp_group_documents_remove_data');

    /**
     * bp_group_documents_register_taxonomies()
     *
     * registers the taxonomies to use with the Wordpress Custom Taxonomy API
     */
    function bp_group_documents_register_taxonomies() {
        register_taxonomy('group-documents-category' , 'group-document' , array('hierarchical' => true , 'label' => __('Group Document Categories' , 'bp-group-documents') , 'query_var' => false));
    }

    add_action('init' , 'bp_group_documents_register_taxonomies');

    /**
     * bp_group_document_set_cookies()
     *
     * Set any cookies for our component.  This will usually be for list filtering and sorting.
     * We must create a dedicated function for this, to fire before the headers are sent
     * (doing this in the template object with the rest of the filtering/sorting is too late)
     */
    function bp_group_documents_set_cookies() {
        if ( isset($_GET['order']) ) {
            setcookie('bp-group-documents-order' , $_GET['order'] , time() + 60 * 60 + 24); //expires in one day
        }
        if ( isset($_GET['category']) ) {
            setcookie('bp-group-documents-category' , $_GET['category'] , time() + 60 * 60 * 24);
        }
    }

    add_action('wp' , 'bp_group_documents_set_cookies');