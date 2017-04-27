<?php

/**
 * See: https://developers.facebook.com/docs/messenger-platform/messenger-profile/persistent-menu
 */

class FBMenuItem {
	public $type;
	public $title;
	public function __construct() {
		return $this;
	}
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}
}

class FBMenuItemUrl extends FBMenuItem {
	public $url;
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

class FBMenuItemUrlWithExtensions extends FBMenuItemUrl {
	public $messenger_extensions;
	public $fallback_url;
	public function __construct() {
		$this->type = 'web_url';
		$this->messenger_extensions = true;
		return $this;
	}
	public function setFallbackUrl($fallback_url) {
		$this->fallback_url = $fallback_url;
		return $this;
	}
}

class FBMenuItemPostback extends FBMenuItem {
	public $payload;
	public function __construct() {
		$this->type = 'postback';
		return $this;
	}
	public function setPayload($payload) {
		$this->payload = $payload;
		return $this;
	}
}

class FBMenuItemNested extends FBMenuItem {
	public $call_to_actions;
	public function __construct() {
		$this->type = 'nested';
		return $this;
	}
	public function setCallToActions($call_to_actions) {
		$this->call_to_actions = $call_to_actions;
		return $this;
	}
}
