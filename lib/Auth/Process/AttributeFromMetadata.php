<?php

/**
 * Filter to add attributes based on metadata of asserting IdP.
 *
 * This filter allows you to add attributes to the attribute set being processed.
 * The value(s) of an attribute are taken from the metadata for the asserting IdP.
 *
 * @author Scott Koranda, SCG
 */
class sspmod_idpsamlmdattributes_Auth_Process_AttributeFromMetadata extends \SimpleSAML\Auth\ProcessingFilter {

  /**
   * Metadata elements we know and that can be configured
   * to be consumed and mapped to an attribute name.
   */
  private $metadata_elements = array(
    'DisplayName',
    'OrganizationName',
    'OrganizationDisplayName'
  );

  /**
   * Mapping of metadata elements to attribute names.
   *
   */
  private $map = array();


  /**
   * Initialize this filter.
   *
   * @param array $config  Configuration information about this filter.
   * @param mixed $reserved  For future use.
   */
  public function __construct($config, $reserved) {
    parent::__construct($config, $reserved);

    assert('is_array($config)');

    foreach($config as $name => $value) {
      if(!in_array($name, $this->metadata_elements)) {
        throw new Exception('Unknown metadata element: ' . $name);
      }
      $this->map[$name] = $value;
    }
  }

  /**
   * Apply filter to add attributes.
   *
   * Add attributes from metadata with the configured name.
   *
   * @param array &$request  The current request
   */
  public function process(&$request) {
    assert('is_array($request)');
    assert('array_key_exists("Attributes", $request)');

    $attributes =& $request['Attributes'];

    foreach($this->map as $metadata_name => $attribute_name) {
      $value = array();
      switch ($metadata_name) {
        case 'DisplayName':
          if(isset($request['Source']['UIInfo']['DisplayName']['en'])) {
            $value[] = $request['Source']['UIInfo']['DisplayName']['en'];
          }
          break;

        case 'OrganizationName':
          if(isset($request['Source']['OrganizationName']['en'])) {
            $value[] = $request['Source']['OrganizationName']['en'];
          }
          break;

        case 'OrganizationDisplayName':
          if(isset($request['Source']['OrganizationDisplayName']['en'])) {
            $value[] = $request['Source']['OrganizationDisplayName']['en'];
          }
          break;
      }

      if(!empty($value)) {
        $attributes[$attribute_name] = $value;
      }
    }
  }
}
