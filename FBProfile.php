<?php

/**
 * This class provides some basic methods of sending messages via the Messenger Send API.
 */

class FBProfile {
	const PROFILE_API_URI_ROOT = 'https://graph.facebook.com/v2.6/';

	/**
	 * Gets profile info
	 */
	public static function get($userId, $fields) {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$fieldsString = implode(',', $fields);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::PROFILE_API_URI_ROOT.$userId.'?fields='.$fieldsString.'&access_token='.FB_ACCESS_TOKEN);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		$output = curl_exec($ch);
		curl_close($ch);
		
		return json_decode($output, true);
	}
}