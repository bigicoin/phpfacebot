<?php

/**
 * We set up a fatal handler, because FB Messenger webhook doesn't take 500 errors gracefully...
 */
register_shutdown_function("gracefulFail");
function gracefulFail() {
	$lasterror = error_get_last();
	if ($lasterror['type'] == E_ERROR || $lasterror['type'] == E_PARSE || $lasterror['type'] == E_CORE_ERROR || $lasterror['type'] == E_COMPILE_ERROR) {
		header("HTTP/1.1 200 Ok");
		echo 'An error has occurred.';
		exit(1);
	}
}

/**
 * App code and dependencies begin
 */

define('APP_BASE', dirname( __FILE__ ) . '/..');
define('PHP_BASE', dirname( __FILE__ ) . '/internal');

require_once(APP_BASE . '/Config.php');
require_once(APP_BASE . '/Redis.php');
require_once(APP_BASE . '/Toro.php');
require_once(APP_BASE . '/UserData.php');
require_once(APP_BASE . '/Tracking.php');
require_once(PHP_BASE . '/WebhookReceiveHandler.php');
require_once(PHP_BASE . '/StaticPagesHandlers.php');

/**
 * Handle IE8 and 9 jquery ajax requests:
 * IE8 and 9 does not natively allow cross domain ajax xmlHttpRequests.
 * They require the use of XDomainRequest and we use a library by MoonScript to handle it.
 * The catch is POST data from those requests come as Content-Type: text/plain,
 * instead of application/x-www-form-urlencoded. As such, PHP does not handle placing it into $_POST.
 */
if (empty($_POST) && !empty($HTTP_RAW_POST_DATA)) {
	// note that we would need to use 'global' to access HTTP_RAW_POST_DATA if inside a function
	parse_str($HTTP_RAW_POST_DATA, $_POST);
}

/**
 * The actual url routing map for our app
 */

Toro::serve(array(
	'/' => 'HomeHandler',
	'/webhookreceive' => 'WebhookReceiveHandler',
	'/webviewTest' => 'WebviewTestHandler',
	'/terms' => 'TermsHandler',
	'/contact' => 'ContactHandler'
));
