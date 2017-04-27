<?php

/**
 * This class provides some methods to set up settings for the bot app.
 * Note: FB is deprecating the "Thread Settings" API (which is FBSettings.php in this framework).
 * The Messenger Profile API, which is this file, is superceding it.
 */

class FBMessengerProfile {
	const MESSENGER_PROFILE_URI = 'https://graph.facebook.com/v2.6/me/messenger_profile';

	/**
	 * Set up the Persistent Menu for this app (globally, across all users!)
	 * Note: FB allows setting multiple locales in one call. However, we won't support this in this framework for now.
	 * Since you can achieve the same results with just multiple API calls, and it's a bit more work to handle
	 * multiple locales in the design of the function signature.
	 * If this feature is used often, we can add support for setting multiple at once.
	 */
	public static function persistentMenu($menuItems, $disableComposer = false, $locale = 'default') {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'persistent_menu' => array(
				array(
					'locale' => $locale,
					'composer_input_disabled' => $disableComposer,
					'call_to_actions' => $menuItems
				)
			)
		));

		$output = self::makeCurl(self::MESSENGER_PROFILE_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Set up the Get Started button for this app (globally, across all users!).
	 * The payload here is a custom payload. You can use the CustomPayload::create()
	 * method to create one that's compatible with the rest of the framework for processing.
	 */
	public static function getStarted($payload) {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'get_started' => array(
				'payload' => $payload
			)
		));

		$output = self::makeCurl(self::MESSENGER_PROFILE_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Set up the Greeting Text for this app (globally, across all users!).
	 * Note: FB allows setting multiple locales in one call. However, we won't support this in this framework for now.
	 * Since you can achieve the same results with just multiple API calls, and it's a bit more work to handle
	 * multiple locales in the design of the function signature.
	 * If this feature is used often, we can add support for setting multiple at once.
	 */
	public static function greetingText($text, $locale = 'default') {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'greeting' => array(
				array(
					'locale' => $locale,
					'text' => $text
				)
			)
		));

		$output = self::makeCurl(self::MESSENGER_PROFILE_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Set up the domain whitelist, for use with webviews and webview extensions.
	 * $domains is a regular array of domains, including the "https://" protocol.
	 */
	public static function domainWhitelist($domains) {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'whitelisted_domains' => $domains
		));

		$output = self::makeCurl(self::MESSENGER_PROFILE_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Utility method to make the curl call
	 */
	private static function makeCurl($uri, $postbody) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $uri.'?access_token='.FB_ACCESS_TOKEN);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postbody);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
}
