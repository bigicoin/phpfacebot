<?php

/**
 * See: https://developers.facebook.com/docs/messenger-platform/send-api-reference/buttons
 */

class FBButton {
	public $type;
	public function __construct() {
		return $this;
	}
}

class FBButtonUrl extends FBButton {
	public $url;
	public $title;
	public $webview_height_ratio;
	public $webview_share_button;
	public function __construct() {
		$this->type = 'web_url';
		return $this;
	}
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}
	public function setTitle($title) {
		// 20 character limit
		$this->title = $title;
		return $this;
	}
	public function setWebviewHeightRatio($webview_height_ratio) {
		// must be compact, tall, or full
		$this->webview_height_ratio = $webview_height_ratio;
		return $this;
	}
	public function setWebviewShareButtonHidden() {
		$this->webview_share_button = 'hide';
		return $this;
	}
}

class FBButtonUrlWithExtensions extends FBButtonUrl {
	public $messenger_extensions;
	public $fallback_url;
	public function __construct() {
		$this->type = 'web_url';
		$this->messenger_extensions = true;
		return $this;
	}
	public function setUrl($url) {
		$this->url = $url;
		$this->fallback_url = $url;
		return $this;
	}
	public function setFallbackUrl($fallback_url) {
		$this->fallback_url = $fallback_url;
		return $this;
	}
}

class FBButtonPostback extends FBButton {
	public $title;
	public $payload;
	public function __construct() {
		$this->type = 'postback';
		return $this;
	}
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}
	public function setPayload($payload) {
		$this->payload = $payload;
		return $this;
	}
}

class FBButtonShare extends FBButton {
	public function __construct() {
		$this->type = 'element_share';
		return $this;
	}
}

// https://developers.facebook.com/docs/messenger-platform/send-api-reference/buy-button
class FBButtonBuy extends FBButton {
	public $title;
	public $payload;
	public $payment_summary;
	public function __construct() {
		$this->type = 'payment';
		$this->title = 'buy'; // required
		return $this;
	}
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}
	public function setPayload($payload) {
		$this->payload = $payload;
		return $this;
	}
	public function setPaymentSummary($payment_summary) {
		// TBD
		$this->payment_summary = $payment_summary;
		return $this;
	}
}
