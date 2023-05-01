<?php
/**
 * Custom XProfile field type for BuddyPress Attachments.
 *
 * @package BP_Attachment_XProfile_Field_Type
 */

/**
 * Custom XProfile field type for BuddyPress Attachments.
 */
class BP_Attachment_XProfile_Field_Type extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'BP Attachment';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Display the uploaded file.
	 *
	 * @param mixed      $field_value Field value.
	 * @param string|int $field_id    ID of the field.
	 * @return mixed
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		$parsed_url = parse_url( $field_value );

		if ( ! empty( $parsed_url['path'] ) ) {
			$href = apply_filters( 'bpaxft_display_href', $parsed_url['path'], $field_value, $field_id );
			$text = apply_filters( 'bpaxft_display_text', 'View file', $field_value, $field_id );

			$retval = apply_filters(
				'bpaxft_display_html',
				sprintf(
					'<a href="%s" target="_blank" rel="nofollow">%s</a>',
					$href,
					$text
				),
				$field_value,
				$field_id
			);
		} else {
			$retval = false;
		}

		return $retval;
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function edit_field_html( array $raw_properties = [] ) {
		$action     = esc_attr( BP_Attachment_XProfile::ACTION );
		$file_input = esc_attr( BP_Attachment_XProfile::FILE_INPUT );

		?>
		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
		</label>
		<?php

		$existing_file = bp_get_the_profile_field_value();
		if ( $existing_file ) {
			echo sprintf(
				'<p>Upload a new file to replace the existing one: %s</p>',
				bp_get_the_profile_field_value()
			);
		}

		do_action( bp_get_the_profile_field_errors_action() );

		// The script below is a hack to get around the fact that there's no filter/action to adjust enctype on profile form.
		?>
		<script>jQuery( 'form#profile-edit-form' ).attr( 'enctype', 'multipart/form-data' );</script>
		<input type="hidden" name="action" id="action" value="<?php echo $action; ?>" />
		<input type="hidden" name="bpaxft_field_id" value="<?php echo bp_get_the_profile_field_id(); ?>" />
		<input type="file" name="<?php echo $file_input; ?>" id="<?php echo $file_input; ?>" />
		<?php
	}

	/**
	 * Same as edit_field_html().
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function admin_field_html( array $raw_properties = [] ) {
		$this->edit_field_html();
	}

	/**
	 * There are no validation rules yet...
	 *
	 * @param string|array $values Value to check against the registered formats.
	 * @return bool True if the value validates
	 */
	public function is_valid( $values ) {
		return true;
	}
}
