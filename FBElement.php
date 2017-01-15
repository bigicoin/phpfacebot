<?php

require_once(dirname(__FILE__).'/FBButton.php');

/**
 * For use with carousel (generic) template or list template
 * See:
 * https://developers.facebook.com/docs/messenger-platform/send-api-reference/generic-template
 * https://developers.facebook.com/docs/messenger-platform/send-api-reference/list-template
 */

class FBElement {
	public $title;
	public $subtitle;
	public $image_url;
	public $buttons;
	public function __construct() {
		return $this;
	}
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}
	public function setSubtitle($subtitle) {
		$this->subtitle = $subtitle;
		return $this;
	}
	public function setImageUrl($image_url) {
		$this->image_url = $image_url;
		return $this;
	}
	public function setButtons($buttons) {
		foreach ($buttons as $button) {
			if (!($button instanceof FBButton)) {
				error_log('Adding invalid Button in FBElement');
				return $this;
			}
		}
		$this->buttons = $buttons;
		return $this;
	}
}

class FBGenericElement extends FBElement {
	public $item_url;
	public function setItemUrl($item_url) {
		$this->item_url = $item_url;
		return $this;
	}
}

class FBListElement extends FBElement {
	public $default_action; // is a FBButton
	public function setDefaultAction($default_action) {
		if (!($default_action instanceof FBButton)) {
			error_log('Adding invalid Defaut Action in FBElement');
			return $this;
		}
		$this->default_action = $default_action;
		return $this;
	}
}
