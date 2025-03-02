<?php 
// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * CB Core Secrets Manager
 * 
 * Manages our secret stash in the mysterious unknown.
 * No touchies. 
 * 
 * @link https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/secretsmanager-examples-manage-secret.html More Info
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */

require Confetti_Bits()->plugin_dir . 'vendor/autoload.php';

use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;

/**
 * CB Core Generate UUID
 * 
 * Generates a UUID that we can use to create unique
 * keys and other neat things for API validation and
 * the like. "Cryptographically secure", unless someone
 * is using a quantum computer. In which case... you're
 * really picking this program as the thing you want to
 * hack? Really?
 * 
 * @return string An RFC 4211 compliant universally unique identifier.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_generate_uuid($bytes = 32) {

	if ( empty($bytes) || !is_int($bytes) ) {
		throw new Exception("UUID must be generated from an integer of bytes.");
	}

	$data = random_bytes($bytes);

	$data[6] = chr(ord($data[6]) & 0x0F | 0x40);
	$data[8] = chr(ord($data[8]) & 0x3F | 0x80);

	// Convert the binary data to a string representation
	$uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

	return $uuid;

}

/**
 * CB Core Set API Key
 * 
 * Sets the encryption key that we use to encrypt and
 * decrypt our API key in the database. 
 * 
 * @param array $args { 
 *     Array of arguments.
 * 
 *   @type string $Name 		Required. A name for the secret. We hang
 * 								onto this and use it to pull the secret
 * 								from our secrets manager.
 * 
 *   @type string $Description	Optional. A description for the secret.
 * 								Nice to have if you want, but not
 * 								entirely necessary.
 * 
 *   @type string $SecretString	Required. This is a secret! Handle
 * 								with extreme care. Please.
 * 
 * }
 * 
 * @return string Results of the createSecret method, or error message on failure.
 */
function cb_core_set_api_key( $args = [] ) {

	$r = wp_parse_args( $args, [
		'Name' => "",
		'Description' => "",
		'SecretString' => "",
	]);

	if ( empty( $r['Name'] ) || empty( $r['SecretString'] ) ) {
		return;
	}

	$client = new SecretsManagerClient([
		'version' => '2017-10-17',
		'region' => 'us-east-1'
	]);

	try {
		$result = $client->createSecret($r);
	} catch (AwsException $e) {
		$result = $e->getAwsErrorMessage();
	}

	return $result;

}

/**
 * CB Core Get API Key
 * 
 * Pulls the API key from our secrets manager. 
 * You may wanna study up on AWS!
 * 
 * @return string The API key. Use with caution, share with none.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_get_api_key( $secret = '' ) {

	if ( empty( $secret ) || ! $secret ) {
		return;
	}
	
	

	$client = new SecretsManagerClient([
		'version' => '2017-10-17',
		'region' => 'us-east-1',
	]);

	try {
		
		$result = $client->getSecretValue(['SecretId' => $secret]);
		
	} catch (AwsException $e) {
		
		return $e->getAwsErrorMessage();
	}
	
	

	// Depending on whether the secret is a string or binary, one of these fields will be populated.
	if (isset($result['SecretString'])) {
		$secret = $result['SecretString'];
	} else {
		$secret = base64_decode($result['SecretBinary']);
	}

	return $secret;

}

/**
 * CB Core Secrets Manager Init
 * 
 * Creates our first set of API credentials automatically.
 * Can refresh them at any time via WordPress admin panel.
 * 
 * @TODO: Add the aforementioned WordPress admin panel.
 * 
 * @return string The stringified Model data, or error message on failure.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_secrets_manager_init() {

	$current_api_key = get_option( 'cb_core_api_key_safe_name' );

	if ( false === $current_api_key ) {

		$api_key_safe_name = "cb-core-api-key-" . cb_core_generate_uuid();
		$api_key = cb_core_generate_uuid();

		add_option( 'cb_core_api_key_safe_name', $api_key_safe_name );

		return cb_core_set_api_key([
			'Name' => $api_key_safe_name,
			'Description' => "An API Key for Confetti Bits.",
			'SecretString' => $api_key,
		]);
	}

}

/**
 * CB Core Update API Key
 * 
 * Creates a new API key in our secrets manager and 
 * updates its safe name in the options table.
 * 
 * @return string The stringified Model data, or error message on failure.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_update_api_key() {

	$api_key_safe_name = "cb-core-api-key-" . cb_core_generate_uuid();
	$api_key = cb_core_generate_uuid();

	update_option( 'cb_core_api_key_safe_name', $api_key_safe_name );

	return cb_core_set_api_key([
		'Name' => $api_key_safe_name,
		'Description' => "An API Key for Confetti Bits.",
		'SecretString' => $api_key,
	]);

}

/**
 * Validates against a supplied API key safe name.
 * 
 * We work with safe names here. We store the safe name 
 * in our DB, and that works as the current valid API key
 * for the current website. If the safe name that gets supplied
 * does not match our safe name, it will fail the test.
 * If the supplied safe name matches, it will then search our
 * secrets manager for a valid API key. If one is not found,
 * or it is expired or invalid, it will also fail.
 * 
 * @param string $safe_name The safe name for our API key.
 * 
 * @return bool Whether the API key exists and is valid.
 * 
 * @package ConfettiBits\Core
 * @since 2.3.0
 */
function cb_core_validate_api_key( $safe_name = '' ) {

	if ( empty( $safe_name ) ) {
		return false;
	}
	
	

	$valid_safe_name = get_option( 'cb_core_api_key_safe_name' );

	if ( $safe_name !== $valid_safe_name ) {
		return false;
	}
	
	

	$valid_api_key = cb_core_get_api_key( $valid_safe_name );
	$testing_api_key = cb_core_get_api_key( $safe_name );

	if ( $testing_api_key !== $valid_api_key ) {
		return false;
	}

	return true;

}