<?php

require_once(APP_BASE . '/UserData.php');
require_once(APP_BASE . '/WitApi.php');

/**
 * Simple DemoBot class that contains code to process a message received through the webhook.
 * Prepares some kind of response and send it back to the sender user.
 */

class DemoBot {
	private static $user;

	public static function process($message) {
		$type = $message->getMessageType();
		$sender = $message->getSender();

		$isNew = self::handleInstall($sender, $message);

		$output = new FBOutgoingMessage($sender);
		$output->sendMarkSeen(); // mark message as seen first before any processing.
		
		if ($type == 'text') {

			$messageText = trim($message->getText());

			if (strtolower($messageText) == 'quicklocation') {

				// the "quicklocation" test command
				$output
					->setText('You should get a Send Location button.')
					->addQuickReply(new FBQuickReplyLocation())
					->send();

			} else if (strtolower($messageText) == 'quickreply') {

				// the "quickreply" test command
				$output->setText('You should get some Quick Reply buttons.');
				$quickReply = new FBQuickReplyText();
				$quickReply->setTitle('Yes')->setPayload(CustomPayload::create('testType', array('val' => 'yes')));
				$output->addQuickReply($quickReply);
				$quickReply = new FBQuickReplyText();
				$quickReply->setTitle('No')->setPayload(CustomPayload::create('testType', array('val' => 'no')));
				$output->addQuickReply($quickReply);
				$output->send();

			} else if (strtolower($messageText) == 'webview') {

				// the "webview" test command
				$buttons = array(new FBButtonUrlWithExtensions(), new FBButtonUrlWithExtensions(), new FBButtonUrlWithExtensions());
				$buttons[0]->setUrl('https://'.HOSTNAME_WWW.'/webviewTest')->setTitle('Compact')->setWebviewHeightRatio('compact');
				$buttons[1]->setUrl('https://'.HOSTNAME_WWW.'/webviewTest')->setTitle('Tall')->setWebviewHeightRatio('tall');
				$buttons[2]->setUrl('https://'.HOSTNAME_WWW.'/webviewTest')->setTitle('Full')->setWebviewHeightRatio('full');
				$output->setButtonTemplate('These are some web view buttons.', $buttons)->send();

			} else {

				// in some cases, a user types in the text of what a quick reply button says. handle that case.
				$quickreplyPayload = self::checkMatchingQuickReply($messageText);
				if (!empty($quickreplyPayload)) {
					self::processButtonPayload($quickreplyPayload, $output);
				} else {

					// generic message handling. As a demo to use wit.ai, let's first try parsing the user's intent.
					// because wit.ai API call can take a while, let user know we're "thinking" by sending typing on.
					$output->sendTypingOn();
					$intent = self::parseIntent($messageText);
					if (!empty($intent)) {

						// specific response for having parsed an intent successfully
						$output->setText('Looks like your intent is '.$intent)->send();

					} else {

						// generic response for random texts
						$buttons = array(new FBButtonPostback(), new FBButtonPostback());
						$buttons[0]->setTitle('Yes')->setPayload(CustomPayload::create('testType', array('val' => 'yes')));
						$buttons[1]->setTitle('No')->setPayload(CustomPayload::create('testType', array('val' => 'no')));
						$output->setButtonTemplate("Hey ".self::$user->getFirstName()."! You said:\n".$messageText, $buttons);
						$output->send();

					}

				}

			}

		} else if ($type == 'location') {

			// handle location
			list($latitude, $longitude) = $message->getLocation();
			$output->setText('I read the location: '.$latitude.', '.$longitude)->send();

		} else if ($type == 'postback') {

			// postback from the demo buttons being clicked
			$payloadRaw = $message->getPostbackPayload();
			$payload = new CustomPayload($payloadRaw);
			self::processButtonPayload($payload, $output);

		} else if ($type == 'quickreply') {

			// postback from the demo quickreply buttons being clicked
			$payloadRaw = $message->getQuickReplyPayload();
			$payload = new CustomPayload($payloadRaw);
			self::processButtonPayload($payload, $output);

		} else if ($type == 'optin') {

			// opt in button (web plugin feature)
			$ref = $message->getOptinRef();
			$output->setText('You got it! '.$ref)->send();
							
		} else if ($type == 'sticker') {
			
			// handle stickers
			$sticker = $message->getStickerId();
			$output->setText('I got your sticker! '.$sticker)->send();
			
		} else if ($type == 'image') {

			// handle images
			$imageUrl = $message->getImage();
			$output->setText('I got your image! Here is your image back in a general template.')->send();

			$element = new FBGenericElement();
			$element->setTitle('Your Image')->setImageUrl($imageUrl)->setSubtitle('This should be your image.');
			$output->setGenericTemplate(array($element))->send();

		} else {

			// other types of messages received. error_log it to check later?
			error_log('Other type received: '.$type);
			error_log($HTTP_RAW_POST_DATA);
			$output->setText('I am not handling this type of message now: '.$type)->send();

		}

		// save quick replies sent or clear them
		self::cacheQuickReplies();
	}

	/**
	 * Process payload, can be either button or quick reply payloads.
	 */
	private static function processButtonPayload($payload, $output) {
		if ($payload->isValid()) {
			if ($payload->getType() == 'testType') {
				if ($payload->getData('val') == 'yes') {
					$output->setText('Yes button clicked!')->send();
				} else {
					$output->setText('No button clicked :(')->send();
				}
			} else if ($payload->getType() == 'getStartedButton') {
				$output->setText('You got started!')->send();
			} else if ($payload->getType() == 'menuButton') {
				$output->setText('You pressed Menu Button!')->send();
			}
		}
	}

	/**
	 * Natural Language Processing using Wit.ai
	 */
	private static function parseIntent($text) {
		$wit = new WitApi(array('access_token' => WIT_AI_SERVER_TOKEN));
		$witResult = $wit->text_query($text);
		$intent = null;
		if (!empty($witResult) && !empty($witResult['code']) && $witResult['code'] == 200) {
			if (!empty($witResult['data']) && !empty($witResult['data']['entities'])) {
				if (!empty($witResult['data']['entities']['intent']) && !empty($witResult['data']['entities']['intent'][0])) {
					// if it found anything at all, regardless of confidence, just treat it as it
					$intent = $witResult['data']['entities']['intent'][0]['value'];
				}
			}
		}
		return $intent;
	}

	/**
	 * Handle install work, like tracking, saving user data to data store.
	 */
	private static function handleInstall($userId, $fbIncomingMessage = null) {
		$isNew = UserData::recordUser($userId);
		self::$user = UserData::init($userId);
		if (!empty($isNew)) {
			$installRef = $fbIncomingMessage->getPostbackReferral();
			$postbackPayload = $fbIncomingMessage->getPostbackPayload();
			if (empty($installRef) && !empty($postbackPayload)) {
				// a new user who clicked on an ad with our json button will fall into this case
				$installRef = $postbackPayload;
			}
			self::$user->getFirstName(); // trigger FB API call to query user info
			Tracking::track('install', array('user_id' => $userId, 'locale' => self::$user->getLocale(), 'timezone' => self::$user->getTimezone(),
				'gender' => self::$user->getGender(), 'is_payment_enabled' => self::$user->getIsPaymentEnabled(), 'ref' => $installRef));
		}
		return $isNew;
	}

	/**
	 * Cache last sent quick replies
	 */
	public static function cacheQuickReplies() {
		if (empty(FBOutgoingMessage::$quickRepliesSent)) {
			self::$user->saveLastQuickReplies('');
		} else {
			$toSave = array();
			foreach (FBOutgoingMessage::$quickRepliesSent as $quickReply) {
				if ($quickReply->content_type != 'location') {
					$toSave[$quickReply->title] = $quickReply->payload;
				}
			}
			if (!empty($toSave)) {
				self::$user->saveLastQuickReplies(json_encode($toSave));
			}
		}
	}

	/**
	 * Check to see if the user typed in something that was a quick reply prompt from us
	 */
	public static function checkMatchingQuickReply($text) {
		$lastQuickReplies = self::$user->getLastQuickReplies();
		$text = trim($text, " \t\n\r\0\x0B,.!?"); // strip all the common puncutations too from the end
		$text = strtolower($text);
		$payload = null;
		if (!empty($lastQuickReplies)) {
			$lastQuickReplies = json_decode($lastQuickReplies, true);
			foreach ($lastQuickReplies as $label => $jsonPayload) {
				if (strtolower($label) == $text) {
					// match
					$payload = new CustomPayload($jsonPayload);
					break;
				}
			}
		}
		return $payload;
	}
}
