<?php

/**
 * See: https://developers.facebook.com/docs/messenger-platform/send-api-reference/quick-replies
 */

class FBQuickReply {
	public $content_type;
	public function __construct() {
		return $this;
	}
}

class FBQuickReplyText extends FBQuickReply {
	public $title;
	public $payload;
	public $image_url;
	public function __construct() {
		$this->content_type = 'text';
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
	public function setImageUrl($image_url) {
		$this->image_url = $image_url;
		return $this;
	}
}

class FBQuickReplyLocation extends FBQuickReply {
	public function __construct() {
		$this->content_type = 'location';
		return $this;
	}
}