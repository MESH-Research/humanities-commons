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
                        'arlisna',
                        array(
                                'labels' => array(
                                        'name' => 'ARLIS/NA',
                                        'singular_name' => 'ARLIS/NA',
                                ),
                                'has_directory' => 'arlisna'
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
                        'hc',
                        array(
                                'labels' => array(
                                        'name' => 'HC',
                                        'singular_name' => 'HC',
                                ),
                                'has_directory' => 'hc'
                        ) );
                bp_register_member_type(
                        'hub',
                        array(
                                'labels' => array(
                                        'name' => 'HUB',
                                        'singular_name' => 'HUB',
                                ),
                                'has_directory' => 'hub'
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
                        'msu',
                        array(
                                'labels' => array(
                                        'name' => 'MSU',
                                        'singular_name' => 'MSU',
                                ),
                                'has_directory' => 'msu'
                        ) );
                bp_register_member_type(
                        'sah',
                        array(
                                'labels' => array(
                                        'name' => 'SAH',
                                        'singular_name' => 'SAH',
                                ),
                                'has_directory' => 'sah'
                        ) );
                bp_register_member_type(
                        'socsci',
                        array(
                                'labels' => array(
                                        'name' => 'SOCSCI',
                                        'singular_name' => 'SOCSCI',
                                ),
                                'has_directory' => 'socsci'
                        ) );
                bp_register_member_type(
                        'stem',
                        array(
                                'labels' => array(
                                        'name' => 'STEM',
                                        'singular_name' => 'STEM',
                                ),
                                'has_directory' => 'stem'
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
                bp_register_member_type(
                        'hastac',
                        array(
                                'labels' => array(
                                        'name' => 'HASTAC',
                                        'singular_name' => 'HASTAC',
                                ),
                                'has_directory' => 'hastac'
                        ) );
                bp_register_member_type(
                        'dhri',
                        array(
                                'labels' => array(
                                        'name' => 'DHRI',
                                        'singular_name' => 'DHRI',
                                ),
                                'has_directory' => 'dhri'
                        ) );
        }
        add_action( 'bp_register_member_types', 'hcommons_register_member_types' );

	function hcommons_register_group_types() {

		bp_groups_register_group_type(
			'arlisna',
			array(
				'labels' => array(
					'name' => 'ARLIS/NA',
					'singular_name' => 'ARLIS/NA',
				),
				'has_directory' => 'arlisna'
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
			'hc',
			array(
				'labels' => array(
					'name' => 'HC',
					'singular_name' => 'HC',
				),
				'has_directory' => 'hc'
			) );
		bp_groups_register_group_type(
			'hub',
			array(
				'labels' => array(
					'name' => 'HUB',
					'singular_name' => 'HUB',
				),
				'has_directory' => 'hub'
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
			'msu',
			array(
				'labels' => array(
					'name' => 'MSU',
					'singular_name' => 'MSU',
				),
				'has_directory' => 'msu'
			) );
		bp_groups_register_group_type(
			'sah',
			array(
				'labels' => array(
					'name' => 'SAH',
					'singular_name' => 'SAH',
				),
				'has_directory' => 'sah'
			) );
		bp_groups_register_group_type(
			'socsci',
			array(
				'labels' => array(
					'name' => 'SOCSCI',
					'singular_name' => 'SOCSCI',
				),
				'has_directory' => 'socsci'
			) );
		bp_groups_register_group_type(
			'stem',
			array(
				'labels' => array(
					'name' => 'STEM',
					'singular_name' => 'STEM',
				),
				'has_directory' => 'stem'
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
                bp_groups_register_group_type(
                        'hastac',
                        array(
                                'labels' => array(
                                        'name' => 'HASTAC',
                                        'singular_name' => 'HASTAC',
                                ),
                                'has_directory' => 'hastac'
                        ) );
                bp_groups_register_group_type(
                        'dhri',
                        array(
                                'labels' => array(
                                        'name' => 'DHRI',
                                        'singular_name' => 'DHRI',
                                ),
                                'has_directory' => 'dhri'
                        ) );
	}
        add_action( 'bp_groups_register_group_types', 'hcommons_register_group_types' );

