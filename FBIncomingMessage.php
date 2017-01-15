<?php

/**
 * This class can create an object out of a JSON message that the Facebook Messenger webhook sends us;
 * and then provide some easy methods that app code can use to retrieve various data from it.
 */

/**
 * Sample formats of messages:
 *
 * Text:
 * {"object":"page","entry":[{"id":1234567890,"time":1461300294714,"messaging":[{"sender":{"id":987654321},"recipient":{
 * "id":1234567890},"timestamp":1461300294699,"message":{"mid":"mid.1461300294658:aaaaaaaaaaaaaaaaaa","seq":6,"text":"Hey"}}]}]}
 *
 * Sticker:
 * {"object":"page","entry":[{"id":1234567890,"time":1461300523867,"messaging":[{"sender":{"id":987654321},"recipient":{
 * "id":1234567890},"timestamp":1461300523844,"message":{"mid":"mid.1461300523830:aaaaaaaaaaaaaaaaaa","seq":10,"sticker_id":275796279225027,
 * "attachments":[{"type":"image","payload":{"url":"https:\\/\\/fbcdn-dragon-a.akamaihd.net\\/hphotos-ak-xft1\\/t39.1997-6\\/p100x100\\/851577_275796282558360_1015771912_n.png"}}]}}]}]}
 *
 * Location:
 * {"object":"page","entry":[{"id":1234567890,"time":1461300487266,"messaging":[{"sender":{"id":987654321},"recipient":{
 * "id":1234567890},"timestamp":1461300487191,"message":{"mid":"mid.1461300486944:aaaaaaaaaaaaaaaaaa","seq":9,"attachments":[
 * {"title":"Pinned Location","url":"https:\\/\\/www.facebook.com\\/l.php?u=https\\u00253A\\u00252F\\u00252Fwww.bing.com\\u00252Fmaps\\u00252Fdefault.aspx\\u00253Fv\\u00253D2\\u002526pc\\u00253DFACEBK\\u002526mid\\u00253D8100\\u002526where1\\u00253D37.775989\\u0025252C\\u00252B-122.411242\\u002526FORM\\u00253DFBKPL1\\u002526mkt\\u00253Den-US&h=AAQGY3ugR&s=1&enc=AZPCA2QGVzyMB6kZrRTMGTGT5iVd3mIjNHl47fIjMG8gbuqAVqPt-pKSPtUqoaTHowa5zpVJnkO1a1YZSOUACr-rDK8rZeOhi0dV-m-MvFDgxg",
 * "type":"location","payload":{"coordinates":{"lat":37.775989,"long":-122.411242}}}]}}]}]}
 *
 * Image:
 * {"object":"page","entry":[{"id":"1234567890","time":1467943684734,"messaging":[{"sender":{"id":"987654321"},"recipient":{
 * "id":"1234567890"},"timestamp":1467943684704,"message":{"mid":"mid.1467943684559:aaaaaaaaaaaaaaaaaa","seq":4058,"attachments":[
 * {"type":"image","payload":{"url":"https:\\/\\/scontent.xx.fbcdn.net\\/v\\/t34.0-12\\/13624703_10104562820857253_1960479715_n.jpg?_nc_ad=z-m&oh=6a6995c464997409b55174b3c97044e6&oe=57823602"}}]}}]}]}
 *
 * Delivery confirmation:
 * {"object":"page","entry":[{"id":1234567890,"time":1461649144617,"messaging":[{"sender":{"id":987654321},"recipient":{
 * "id":1234567890},"delivery":{"mids":["mid.1461649144231:aaaaaaaaaaaaaaaaaa"],"watermark":1461649144356,"seq":36}}]}]}
 *
 * Postbacks:
 * {"object":"page","entry":[{"id":1234567890,"time":1461650241976,"messaging":[{"sender":{"id":987654321},"recipient":{
 * "id":1234567890},"timestamp":1461650241976,"postback":{"payload":"hello world"}}]}]}
 *
 * Quick Reply text reply: (similar to text but different)
 * {"object":"page","entry":[{"id":"1234567890","time":1475194561890,"messaging":[{"sender":{"id":"987654321"},"recipient":{
 * "id":"1234567890"},"timestamp":1475194561852,"message":{"quick_reply":{"payload":"Two"},"mid":"mid.1475194561841:aaaaaaaaaaaaaaaaaa","seq":5394,"text":"Two"}}]}]}
 *
 * "Send to Messenger" calls:
 * {"object":"page","entry":[{"id":"1234567890","time":1467180431430,"messaging":[{"sender":{"id":"987654321"},"recipient":{
 * "id":"1234567890"},"timestamp":1467180431430,"optin":{"ref":"PASS_THROUGH"}}]}]}
 *
 * Ref param m.me links:
 *  {"object":"page","entry":[{"id":"1234567890","time":1479854127532,"messaging":[{"recipient":{"id":"1234567890"},
 * "timestamp":1479854127532,"sender":{"id":"987654321"},"referral":{"ref":"someparam","source":"SHORTLINK","type":"OPEN_THREAD"}}]}]}
 */

class FBIncomingBatch {
	/**
	 * Stores the raw JSON-decoded associative array of the batch
	 */
	private $obj;

	private $entryIter;
	private $messagingIter;

	/**
	 * Constructor takes in the raw JSON message.
	 */
	public function __construct($jsonMessage) {
		$givenSig = $_SERVER['HTTP_X_HUB_SIGNATURE'];
		$generatedSig = 'sha1='.hash_hmac('sha1', $jsonMessage, FB_APP_SECRET);
		if ($givenSig == $generatedSig) {
			$this->obj = json_decode($jsonMessage, true);
			$this->entryIter = 0;
			$this->messagingIter = 0;
		} else {
			$this->obj = null;
		}
	}

	/**
	 * Iterate to next entry
	 */
	public function nextEntry() {
		$this->entryIter++;
		if (empty($this->obj['entry']) || empty($this->obj['entry'][$this->entryIter])) {
			$this->entryIter = null;
			return false;
		}
		return true;
	}

	/**
	 * Iterate to next messaging
	 */
	public function nextMessaging() {
		$this->messagingIter++;
		if (empty($this->obj['entry']) || empty($this->obj['entry'][$this->entryIter]) || empty($this->obj['entry'][$this->entryIter]['messaging']) || empty($this->obj['entry'][$this->entryIter]['messaging'][$this->messagingIter])) {
			$this->messagingIter = null;
			return false;
		}
		return true;
	}

	/**
	 * Returns the unix timestamp (in seconds) of the message
	 */
	public function getTimestamp() {
		$result = null;
		if (!empty($this->obj['entry']) && !empty($this->obj['entry'][$this->entryIter]) && !empty($this->obj['entry'][$this->entryIter]['time'])) {
			$result = $this->obj['entry'][$this->entryIter]['time'] / 1000; // because this is millisecond
		}
		return $result;
	}

	/**
	 * Get the Message object from the current entry and messaging point.
	 */
	public function getMessage() {
		return new FBIncomingMessage($this->obj['entry'][$this->entryIter]['messaging'][$this->messagingIter]);
	}

	/**
	 * Get the Message json from the current entry and messaging point.
	 */
	public function getMessageJson() {
		return json_encode($this->obj['entry'][$this->entryIter]['messaging'][$this->messagingIter]);
	}
}

class FBIncomingMessage {
	/**
	 * Stores the raw JSON-decoded associative array of the message
	 */
	private $obj;

	/**
	 * Constructor takes in the raw JSON-decoded associative array.
	 */
	public function __construct($obj) {
		$this->obj = $obj;
	}

	/**
	 * Return sender FB user id
	 */
	public function getSender() {
		$result = null;
		if (!empty($this->obj) && !empty($this->obj['sender'])) {
			$result = $this->obj['sender']['id'];
		}
		return $result;
	}

	/**
	 * Return recipient FB user id
	 */
	public function getRecipient() {
		$result = null;
		if (!empty($this->obj) && !empty($this->obj['recipient'])) {
			$result = $this->obj['recipient']['id'];
		}
		return $result;
	}

	/**
	 * Return the type of this message
	 */
	public function getMessageType() {
		$result = 'unknown';
		if (!empty($this->obj) && !empty($this->obj['message'])) {
			// check for quick reply before text, because quick reply also includes a text
			if (isset($this->obj['message']['quick_reply'])) {
				$result = 'quickreply';
			} else if (isset($this->obj['message']['text'])) {
				$result = 'text';
			} else if (isset($this->obj['message']['sticker_id'])) {
				$result = 'sticker';
			} else if (isset($this->obj['message']['attachments'])) {
				if ($this->obj['message']['attachments'][0]['type'] == 'location') {
					$result = 'location';
				} else if ($this->obj['message']['attachments'][0]['type'] == 'image') {
					$result = 'image';
				}
			}
		} else if (!empty($this->obj['delivery'])) {
			$result = 'delivery';
		} else if (!empty($this->obj['postback'])) {
			$result = 'postback';
		} else if (!empty($this->obj['optin'])) {
			$result = 'optin';
		} else if (!empty($this->obj['referral'])) {
			$result = 'referral';
		}
		return $result;
	}

	/**
	 * If this is a text type message, return the text string.
	 * If not, return null.
	 */
	public function getText() {
		$result = null;
		if (!empty($this->obj) && !empty($this->obj['message'])) {
			if (isset($this->obj['message']['text'])) {
				$result = $this->obj['message']['text'];
			}
		}
		return $result;
	}

	/**
	 * Get quickreply payload if exists.
	 */
	public function getQuickReplyPayload() {
		$result = null;
		if (!empty($this->obj) && !empty($this->obj['message'])) {
			if (isset($this->obj['message']['quick_reply'])) {
				$result = $this->obj['message']['quick_reply']['payload'];
			}
		}
		return $result;
	}

	/**
	 * If this is a sticker message, get sticker id.
	 * If not, return null.
	 */
	public function getStickerId() {
		$result = null;
		if (!empty($this->obj) && !empty($this->obj['message'])) {
			if (isset($this->obj['message']['sticker_id'])) {
				$result = $this->obj['message']['sticker_id'];
			}
		}
		return $result;
	}
		
	/**
	 * If this is a location type message, return lat-long pair in array [lat,long] format.
	 * If not, return null.
	 */
	public function getLocation() {
		$result = null;
		if (!empty($this->obj) && !empty($this->obj['message'])) {
			if (isset($this->obj['message']['attachments'])) {
				if ($this->obj['message']['attachments'][0]['type'] == 'location') {
					$result = array(
						$this->obj['message']['attachments'][0]['payload']['coordinates']['lat'],
						$this->obj['message']['attachments'][0]['payload']['coordinates']['long']
					);
				}
			}
		}
		return $result;
	}

	/**
	 * If this is a image type message, return image url.
	 * If not, return null.
	 */
	public function getImage() {
		$result = null;
		if (!empty($this->obj) && !empty($this->obj['message'])) {
			if (isset($this->obj['message']['attachments'])) {
				if ($this->obj['message']['attachments'][0]['type'] == 'image') {
					$result = $this->obj['message']['attachments'][0]['payload']['url'];
				}
			}
		}
		return $result;
	}

	/**
	 * Get postback payload if exists.
	 */
	public function getPostbackPayload() {
		$result = null;
		if (!empty($this->obj['postback'])) {
			$result = $this->obj['postback']['payload'];
		}
		return $result;
	}

	/**
	 * Get postback referral if exists.
	 */
	public function getPostbackReferral() {
		$result = null;
		if (!empty($this->obj['postback']) && !empty($this->obj['postback']['referral'])) {
			$result = $this->obj['postback']['referral']['ref'];
		}
		return $result;
	}

	/**
	 * Get optin ref if exists.
	 */
	public function getOptinRef() {
		$result = null;
		if (!empty($this->obj['optin'])) {
			$result = $this->obj['optin']['ref'];
		}
		return $result;
	}

	/**
	 * Get referral ref if exists.
	 */
	public function getReferralRef() {
		$result = null;
		if (!empty($this->obj['referral'])) {
			$result = $this->obj['referral']['ref'];
		}
		return $result;
	}
}
