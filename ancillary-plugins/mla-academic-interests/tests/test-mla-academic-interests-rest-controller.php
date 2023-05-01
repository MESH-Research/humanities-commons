<?php
/**
 * Class Mla_Academic_Interests_REST_ControllerTest
 *
 * @package Mla_Academic_Interests
 */

/**
 * Test class
 */
class Mla_Academic_Interests_REST_ControllerTest extends WP_UnitTestCase {

	/**
	 * Ensure sorted terms match expected order.
	 *
	 * @param  array  $matched_terms natcasesort()ed array of term objects containing properties 'id' & 'text' to be sorted.
	 * @param  array  $expected_sorted_terms expected return value of tested method.
	 * @param  string $user_input    search query.
	 * @dataProvider sort_matched_terms_provider
	 */
	function test_sort_matched_terms( array $matched_terms, array $expected_sorted_terms, string $user_input ) {
		$sorted_terms = Mla_Academic_Interests_REST_Controller::sort_matched_terms( $matched_terms, $user_input );

		$this->assertEquals( $sorted_terms, $expected_sorted_terms );
	}

	/**
	 * Provider for sort_matched_terms().
	 */
	function sort_matched_terms_provider() {
		return [
			[
				[
					(object) [
						'id' => 'Digital humanities',
						'text' => 'Digital humanities',
					],
					(object) [
						'id' => 'Digital humanities research and methodology',
						'text' => 'Digital humanities research and methodology',
					],
					(object) [
						'id' => 'History of the book and the digital humanities',
						'text' => 'History of the book and the digital humanities',
					],
					(object) [
						'id' => 'Usability of digital humanities resources',
						'text' => 'Usability of digital humanities resources',
					],
				],
				[
					(object) [
						'id' => 'Digital humanities',
						'text' => 'Digital humanities',
					],
					(object) [
						'id' => 'Digital humanities research and methodology',
						'text' => 'Digital humanities research and methodology',
					],
					(object) [
						'id' => 'History of the book and the digital humanities',
						'text' => 'History of the book and the digital humanities',
					],
					(object) [
						'id' => 'Usability of digital humanities resources',
						'text' => 'Usability of digital humanities resources',
					],
				],
				'digital humanities',
			],
			[
				[
					(object) [
						'id' => 'Film sound',
						'text' => 'Film sound',
					],
					(object) [
						'id' => 'sound',
						'text' => 'sound',
					],
					(object) [
						'id' => 'Sound/sound art',
						'text' => 'Sound/sound art',
					],
					(object) [
						'id' => 'sound art',
						'text' => 'sound art',
					],
					(object) [
						'id' => 'Sound poetry',
						'text' => 'Sound poetry',
					],
					(object) [
						'id' => 'Sound recording technologies',
						'text' => 'Sound recording technologies',
					],
					(object) [
						'id' => 'Sound studies',
						'text' => 'Sound studies',
					],
					(object) [
						'id' => 'Soundtrack',
						'text' => 'Soundtrack',
					],
					(object) [
						'id' => 'Text-sound composition',
						'text' => 'Text-sound composition',
					],
				],
				[
					(object) [
						'id' => 'sound',
						'text' => 'sound',
					],
					(object) [
						'id' => 'Sound/sound art',
						'text' => 'Sound/sound art',
					],
					(object) [
						'id' => 'sound art',
						'text' => 'sound art',
					],
					(object) [
						'id' => 'Sound poetry',
						'text' => 'Sound poetry',
					],
					(object) [
						'id' => 'Sound recording technologies',
						'text' => 'Sound recording technologies',
					],
					(object) [
						'id' => 'Sound studies',
						'text' => 'Sound studies',
					],
					(object) [
						'id' => 'Soundtrack',
						'text' => 'Soundtrack',
					],
					(object) [
						'id' => 'Film sound',
						'text' => 'Film sound',
					],
					(object) [
						'id' => 'Text-sound composition',
						'text' => 'Text-sound composition',
					],
				],
				'sound',
			],
		];
	}
}
