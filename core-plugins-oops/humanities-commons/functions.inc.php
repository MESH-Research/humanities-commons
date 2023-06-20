<?php


/*
 * loadRequestParameters
 * ---------------------
 * Loop through $_POST and load parameters into an array.
 */

function loadRequestParameters () {

  global $_POST;

  // Initialize request array.
  $parameters = array();

  // Request parameters are specified by prefixes of 'param_name/param_value'.
  foreach ( $_POST as $key => $value ):
    if ( strpos($key, 'param_name_') === 0 ):
      $param_id = substr($key, 11);
      $parameters[$value] = $_POST['param_value_' . $param_id];
    endif;
  endforeach;

  return $parameters;

}


/*
 * generateRequest
 * ---------------
 * Compute the API signature using HMAC-SHA256. The HTTP method (e.g., "GET")
 * should be concatenated with the rawurlencoded request URL, separated by an
 * ampersand (“&”), then passed to the hash algorithm. The resulting signature
 * should be appended to the request URL and returned.
 */

function generateRequest ( $http_method, $base_url, $api_secret, $parameters = array() ) {

  // Append current time to request parameters (seconds from UNIX epoch).
  $parameters['timestamp'] = time();

  // Sort the request parameters.
  ksort($parameters);

  // Collapse the parameters into a URI query string.
  $query_string = http_build_query($parameters, '', '&');

  // Add the request parameters to the base URL.
  $request_url = $base_url . '?' . $query_string;

  // Compute the request signature (see specification).
  $hash_input = $http_method . '&' . rawurlencode($request_url);
  $api_signature = hash_hmac('sha256', $hash_input, $api_secret);

  // Append the signature to the request.
  return $request_url . '&signature=' . $api_signature;

}


/*
 * sendRequest
 * -----------
 * Send a RESTful request to the API.
 */

function sendRequest ( $http_method, $request_url, $request_body = '' ) {

  // Initialize a cURL session.
  $ch = curl_init();
  $headers = array('Accept: application/json');

  // Set cURL options.
  curl_setopt($ch, CURLOPT_URL, $request_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FAILONERROR, false);
//  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

  // Validate certificates.
  if ( substr($request_url, 0, 23) === "https://apidev.mla.org/" ):
    // openssl x509 -in /path/to/self-signed.crt -text > self-signed.pem
    curl_setopt($ch, CURLOPT_CAINFO, getcwd() . '/ssl/self-signed.pem');
  elseif ( substr($request_url, 0, 20) === "https://api.mla.org/" ):
    curl_setopt($ch, CURLOPT_CAINFO, getcwd() . '/ssl/self-signed-production.pem');
  //elseif ( substr($request_url, 0, 20) === "https://api.mla.org/" ):
  //  curl_setopt($ch, CURLOPT_CAINFO, getcwd() . '/ssl/cacert.pem');
  endif;

  // Set HTTP method.
  if ( $http_method === 'PUT' || $http_method === 'DELETE' ):
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
  elseif ($http_method === 'POST'):
    curl_setopt($ch, CURLOPT_POST, 1);
  endif;

  // Add request body.
  if ( strlen($request_body) ):
    $headers[] = 'Content-Length: ' . strlen($request_body);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
  endif;

  // Add HTTP headers.
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  // Send request.
  $response_text = curl_exec($ch);

  // Describe error if request failed.
  if ( !$response_text ):
    $response = array(
      'code' => '500',
      'body' => curl_error($ch)
    );
  else:
    $response = array(
      'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
      'body' => $response_text
    );
  endif;

  // Close cURL session.
  curl_close($ch);

  return $response;

}


/*
 * isValidJSON
 * -----------
 * Determines if string can be parsed as JSON.
 * http://stackoverflow.com/questions/6041741/
 */

function isValidJSON ( $str ) {
  json_decode($str);
  return ( json_last_error() === JSON_ERROR_NONE );
}

