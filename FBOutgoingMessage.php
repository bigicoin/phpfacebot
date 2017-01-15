<?php

require_once(dirname(__FILE__).'/FBQuickReply.php');
require_once(dirname(__FILE__).'/FBButton.php');
require_once(dirname(__FILE__).'/FBElement.php');

/**
 * This class builds an outgoing message that can be sent back to the user from the bot.
 */

class FBOutgoingMessage {
	const SEND_API_URI = 'https://graph.facebook.com/v2.6/me/messages';

	public static $quickRepliesSent = array();

	private $obj;

	/**
	 * Construct base object with recipient id
	 */
	public function __construct($recipient) {
		// set up basic structure of FB Reply message
		$this->obj = array(
			'recipient' => array(
				'id' => $recipient
			),
			'message' => array()
		);
		return $this;
	}

	/**
	 * If recipient id has to be set later.
	 */
	public function setRecipient($recipient) {
		$this->obj['recipient']['id'] = $recipient;
		return $this;
	}

	/**
	 * Set the text message to send.
	 * Note that the use of this will reset previous content sets or button adds.
	 */
	public function setText($text) {
		$this->obj['message'] = array(
			'text' => $text
		);
		return $this;
	}

	/**
	 * Set the image to send with an image url.
	 * Note that the use of this will reset previous content sets or button adds.
	 * Note that other similar type of messages available to use are "audio", "video", and "file",
	 * currently not implemented.
	 */
	public function setImage($imageUrl) {
		$this->obj['message'] = array(
			'attachment' => array(
				'type' => 'image',
				'payload' => array(
					'url' => $imageUrl
				)
			)
		);
		return $this;
	}

	/**
	 * Adds a quick reply button to this message.
	 * Must be a valid quick reply object.
	 */
	public function addQuickReply($quickReply) {
		if ($quickReply instanceof FBQuickReply) {
			if (empty($this->obj['message']['quick_replies'])) {
				$this->obj['message']['quick_replies'] = array();
			}
			$this->obj['message']['quick_replies'][] = $quickReply;
		} else {
			error_log('Adding invalid Quick Reply');
		}
		return $this;
	}

	/**
	 * Button Template is some text with some buttons.
	 * Note that button template texts are limited to 320 characters.
	 * Note that the use of this will reset previous content sets or button adds.
	 */
	public function setButtonTemplate($text, $buttons) {
		foreach ($buttons as $button) {
			if (!($button instanceof FBButton)) {
				error_log('Adding invalid Button');
				return $this;;
			}
		}
		$this->obj['message'] = array(
			'attachment' => array(
				'type' => 'template',
				'payload' => array(
					'template_type' => 'button',
					'text' => $text,
					'buttons' => $buttons
				)
			)
		);
		return $this;
	}

	/**
	 * Button Template is some text with some buttons.
	 * Note that the use of this will reset previous content sets or button adds.
	 */
	public function setGenericTemplate($elements) {
		foreach ($elements as $element) {
			if (!($element instanceof FBGenericElement)) {
				error_log('Adding invalid Generic Element');
				return $this;
			}
		}
		$this->obj['message'] = array(
			'attachment' => array(
				'type' => 'template',
				'payload' => array(
					'template_type' => 'generic',
					'elements' => $elements
				)
			)
		);
		return $this;
	}

	/**
	 * Button Template is some text with some buttons.
	 * Note that the use of this will reset previous content sets or button adds.
	 */
	public function setListTemplate($elements, $buttons = array(), $top_element_style = 'large') {
		// $top_element_style must be large or compact
		// $elements max 4
		// $buttons max 1
		foreach ($elements as $element) {
			if (!($element instanceof FBListElement)) {
				error_log('Adding invalid List Element');
				return $this;
			}
		}
		foreach ($buttons as $button) {
			if (!($button instanceof FBButton)) {
				error_log('Adding invalid Button');
				return $this;
			}
		}
		$this->obj['message'] = array(
			'attachment' => array(
				'type' => 'template',
				'payload' => array(
					'template_type' => 'list',
					'top_element_style' => $top_element_style,
					'elements' => $elements,
					'buttons' => $buttons
				)
			)
		);
		return $this;
	}

	/**
	 * Actually make send call to FB API
	 */
	public function send() {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}
		// check for any quick replies sent in this request
		if (!empty($this->obj['message']) && !empty($this->obj['message']['quick_replies'])) {
			self::$quickRepliesSent = $this->obj['message']['quick_replies'];
		}
		$output = self::makeCurl(self::SEND_API_URI, json_encode($this->obj));
		$result = json_decode($output, true);
		$ret = true;
		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
			$ret = $result;
		}
		return $ret;
	}

	/**
	 * Send an image as file attachment upload. This is a special API call by itself.
	 * Note that other similar type of messages available to use are "audio", "video", and "file",
	 * currently not implemented.
	 */
	public function sendImageFile($imageFileWithFullPath) {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}
		$filesize = getimagesize($imageFileWithFullPath);
		$mimetype = $filesize['mime'];
		// construct a special post body for this request
		$postbody = array(
			'recipient' => json_encode(array('id' => $this->obj['recipient']['id'])),
			'message' => '{"attachment":{"type":"image", "payload":{}}}',
			'filedata'=>'@'.$imageFileWithFullPath.';type='.$mimetype
		);
		$output = self::makeCurl(self::SEND_API_URI, $postbody, false);
		$result = json_decode($output, true);
		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}
	}

	/**
	 * Send a "typing" action. This is a special API call by itself.
	 */
	public function sendTypingOn() {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}
		// construct a special post body for this request
		$postbody = array(
			'recipient' => json_encode(array('id' => $this->obj['recipient']['id'])),
			'sender_action' => 'typing_on'
		);
		$output = self::makeCurl(self::SEND_API_URI, $postbody, false);
		$result = json_decode($output, true);
		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}
	}

	/**
	 * Send a "mark_seen" action. This is a special API call by itself.
	 */
	public function sendMarkSeen() {
		if (FB_ACCESS_TOKEN == '') {
			error_log('Error: You probably did not set up your FB access token.');
		}
		// construct a special post body for this request
		$postbody = array(
			'recipient' => json_encode(array('id' => $this->obj['recipient']['id'])),
			'sender_action' => 'mark_seen'
		);
		$output = self::makeCurl(self::SEND_API_URI, $postbody, false);
		$result = json_decode($output, true);
		if (!empty($result['error'])) {
			error_log('Facebook error: '.$output);
		}
	}

	/**
	 * Utility method to make the curl call
	 */
	private static function makeCurl($uri, $postbody, $includeJsonHeader = true) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $uri.'?access_token='.FB_ACCESS_TOKEN);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postbody);
		if ($includeJsonHeader) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
}
