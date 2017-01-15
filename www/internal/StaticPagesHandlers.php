<?php

class HomeHandler {
	/**
	 * Homepage handler (for accessing the root domain).
	 */
	public function get() {
		require(PHP_BASE . '/views/home.php');
	}

	public function post() {
		ToroHook::fire('405', "GET"); // method not allowed
	}
}

class WebviewTestHandler {
	/**
	 * Webview Test Page
	 */
	public function get() {
		require(PHP_BASE . '/views/webviewTest.php');
	}

	public function post() {
		ToroHook::fire('405', "GET"); // method not allowed
	}
}

class TermsHandler {
	/**
	 * Terms page handler.
	 */
	public function get() {
		require(PHP_BASE . '/views/terms.php');
	}

	public function post() {
		ToroHook::fire('405', "GET"); // method not allowed
	}
}

class ContactHandler {
	/**
	 * Contact page handler.
	 */
	public function get() {
		require(PHP_BASE . '/views/contact.php');
	}

	public function post() {
		ToroHook::fire('405', "GET"); // method not allowed
	}
}
