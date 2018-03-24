<?php

require_once(APP_BASE . '/FBIncomingMessage.php');
require_once(APP_BASE . '/FBOutgoingMessage.php');
require_once(APP_BASE . '/CustomPayload.php');
require_once(PHP_BASE . '/DemoBot.php');

class WebhookReceiveHandler {
	/**
	 * GET handler, mostly for Facebook to verify the webhook endpoint only.
	 */
	public function get() {
		error_log('GET request: '.print_r($_GET, true));

		// facebook setup verification case
		if (!empty($_GET['hub_mode']) && $_GET['hub_mode'] == 'subscribe' && !empty($_GET['hub_challenge'])
		&& !empty($_GET['hub_verify_token']) && $_GET['hub_verify_token'] == FB_HUB_VERIFY_TOKEN) {
			echo $_GET['hub_challenge'];
			return;
		}

		// other cases, this does not matter.
		echo 'success';
	}

	/**
	 * POST handler, the real webhook endpoint processing code, for handling user messages
	 */
	public function post() {
		// the first thing we do is return a success, so FB doesn't think our endpoint is down and tries to repeat it.
		$this->acknowledgeRequest();

		// get the raw POST body, which FB sends the message in.
		global $HTTP_RAW_POST_DATA;

		// construct a FBIncomingBatch object out of it.
		$batch = new FBIncomingBatch($HTTP_RAW_POST_DATA);

		do {
			do {
				$message = $batch->getMessage();

				// for delivery confirmation, we ignore it.
				if ($message->getMessageType() == 'delivery') {
					continue;
				}

				// process the message
				DemoBot::process($message);

			} while ($batch->nextMessaging());
		} while ($batch->nextEntry());
	}

	/**
	 * Used for handling webhook endpoint requests.
	 */
	private function acknowledgeRequest() {
		// In order to return a response and continue processing code, we do this trick.
		// this essentially ends the request from the requester (FB)'s perspective.
		// source: http://stackoverflow.com/questions/15273570/continue-processing-php-after-sending-http-response
		set_time_limit(0);
		ob_start();
		// echo 'success'; // FB does not care return message as long as it's 200 OK
		// $size = ob_get_length();
		header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
		header("Status: 200 OK");
		header("Content-Encoding: none"); // disable compression
		// header("Content-Length: {$size}");
		header("Connection: close");
		ob_end_flush();
		ob_flush();
		flush();
	}
}
