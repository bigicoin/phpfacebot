<?php

/**
 * This class provides some methods to set up settings for the bot app.
 * Deprecation note: FB is deprecating the Thread Settings API (this file),
 * in favor of the Messenger Profile API (FBMessengerProfile.php in this framework).
 * Consider using only that class from now on.
 */

class FBSettings {
	const THREAD_SETTINGS_URI = 'https://graph.facebook.com/v2.6/me/thread_settings';

	/**
	 * Set up the Persistent Menu for this app (globally, across all users!)
	 */
	public static function persistentMenu($buttons) {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'setting_type' => 'call_to_actions',
			'thread_state' => 'existing_thread',
			'call_to_actions' => self::formatButtons($buttons)
		));

		$output = self::makeCurl(self::THREAD_SETTINGS_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Set up the Get Started button for this app (globally, across all users!).
	 * Note even though an array of buttons is in the param, only 1 button is accepted.
	 */
	public static function getStarted($buttons) {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'setting_type' => 'call_to_actions',
			'thread_state' => 'new_thread',
			'call_to_actions' => self::formatButtons($buttons)
		));

		$output = self::makeCurl(self::THREAD_SETTINGS_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Set up the Greeting Text for this app (globally, across all users!).
	 */
	public static function greetingText($text) {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'setting_type' => 'greeting',
			'greeting' => array('text' => $text)
		));

		$output = self::makeCurl(self::THREAD_SETTINGS_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Set up the domain whitelist, for use with webviews and webview extensions.
	 * $domains is a regular array of domains, including the "https://" protocol.
	 * $action is add or remove.
	 */
	public static function domainWhitelist($domains, $action = 'add') {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}

		$postbody = json_encode(array(
			'setting_type' => 'domain_whitelisting',
			'whitelisted_domains' => $domains,
			'domain_action_type' => $action
		));

		$output = self::makeCurl(self::THREAD_SETTINGS_URI, $postbody);

		$result = json_decode($output, true);

		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}

		return $result;
	}

	/**
	 * Utility method to format buttons from a simple {buttonLabel=>payload} assoc array to the format that
	 * FB Send API wants to see it in.
	 */
	private static function formatButtons($buttons) {
		$result = array();
		if (!empty($buttons) && is_array($buttons)) {
			foreach ($buttons as $buttonLabel => $payload) {
				if ($payload == 'element_share') {
					$button = array('type' => $payload);
				} else {
					$buttonType = 'postback';
					$payloadName = 'payload';
					$additional = array();
					if (strpos($payload, 'http://') === 0 || strpos($payload, 'https://') === 0 || preg_match('/^(compact|tall|full)\|(http.*)$/', $payload, $webviewMatch) === 1) {
						$buttonType = 'web_url';
						$payloadName = 'url';
						if (!empty($webviewMatch[1])) {
							$additional['webview_height_ratio'] = $webviewMatch[1];
							$additional['messenger_extensions'] = true;
							$payload = $webviewMatch[2];
						}
					}
					$button = array('type' => $buttonType, 'title' => $buttonLabel, "$payloadName" => $payload);
					$button = array_merge($button, $additional);
				}
				$result[] = $button;
			}
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
