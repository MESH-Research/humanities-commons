<?php
/**
 * Society Settings
 *
 * Register member types and group types for all societies
 *
 * @package Humanities Commons
 * @subpackage Configuration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

        function hcommons_register_member_types() {

                bp_register_member_type(
                        'ajs',
                        array(
                                'labels' => array(
                                        'name' => 'AJS',
                                        'singular_name' => 'AJS',
                                ),
                                'has_directory' => 'ajs'
                        ) );
                bp_register_member_type(
                        'aseees',
                        array(
                                'labels' => array(
                                        'name' => 'ASEEES',
                                        'singular_name' => 'ASEEES',
                                ),
                                'has_directory' => 'aseees'
                        ) );
                bp_register_member_type(
                        'caa',
                        array(
                                'labels' => array(
                                        'name' => 'CAA',
                                        'singular_name' => 'CAA',
                                ),
                                'has_directory' => 'caa'
                        ) );
                bp_register_member_type(
                        'hc',
                        array(
                                'labels' => array(
                                        'name' => 'HC',
                                        'singular_name' => 'HC',
                                ),
                                'has_directory' => 'hc'
                        ) );
                bp_register_member_type(
                        'mla',
                        array(
                                'labels' => array(
                                        'name' => 'MLA',
                                        'singular_name' => 'MLA',
                                ),
                                'has_directory' => 'mla'
                        ) );
                bp_register_member_type(
                        'up',
                        array(
                                'labels' => array(
                                        'name' => 'UP',
                                        'singular_name' => 'UP',
                                ),
                                'has_directory' => 'up'
                        ) );
        }
        add_action( 'bp_register_member_types', 'hcommons_register_member_types' );

	function hcommons_register_group_types() {

		bp_groups_register_group_type(
			'ajs',
			array(
				'labels' => array(
					'name' => 'AJS',
					'singular_name' => 'AJS',
				),
				'has_directory' => 'ajs'
			) );
		bp_groups_register_group_type(
			'aseees',
			array(
				'labels' => array(
					'name' => 'ASEEES',
					'singular_name' => 'ASEEES',
				),
				'has_directory' => 'aseees'
			) );
		bp_groups_register_group_type(
			'caa',
			array(
				'labels' => array(
					'name' => 'CAA',
					'singular_name' => 'CAA',
				),
				'has_directory' => 'caa'
			) );
		bp_groups_register_group_type(
			'hc',
			array(
				'labels' => array(
					'name' => 'HC',
					'singular_name' => 'HC',
				),
				'has_directory' => 'hc'
			) );
		bp_groups_register_group_type(
			'mla',
			array(
				'labels' => array(
					'name' => 'MLA',
					'singular_name' => 'MLA',
				),
				'has_directory' => 'mla'
			) );
		bp_groups_register_group_type(
			'up',
			array(
				'labels' => array(
					'name' => 'UP',
					'singular_name' => 'UP',
				),
				'has_directory' => 'up'
			) );
	}
        add_action( 'bp_groups_register_group_types', 'hcommons_register_group_types' );

