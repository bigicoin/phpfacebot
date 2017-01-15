<?php

/**
 * Define all the tokens and IDs relating to the FB Bot App here,
 * as well as server hostnames, IPs, and so on.
 *
 * I use the Apache config file's ServerAdmin value (which becomes $_SERVER['SERVER_ADMIN'] in PHP global)
 * to determine if we're running in production environment, dev environment, and so on.
 * This is by no means a best practice, but just one of the easier ways to have multiple environments
 * running off the same codebase.
 */

switch ($_SERVER['SERVER_ADMIN']) {
	case 'webmaster@yourdomain.com': // production
		define('ENV', 'prod');
		// you should define the rest of production consts like below
		break;

	case 'user@yourdomain.com': // dev
		define('ENV', 'dev');

		define('BOT_NAME', 'PHPFaceBot'); // user facing name that appears on web site, etc.
		define('HOSTNAME_WWW', 'phpfacebot.com'); // your domain or subdomain

		// redis instance for storing user info
		define('REDIS_ENABLED', 1); // disable if you do not have a redis instance setup. demo bot will still work, but some features will be missing.
		define('REDIS_HOST', '127.0.0.1');
		define('REDIS_PORT', 6379);
		define('REDIS_NAMESPACE_PREFIX', 'phpfacebot:');
		define('REDIS_TRACK_QUEUE_NAME', 'track_queue');

		define('FB_APP_ID', '123456789123456'); // fb ids and tokens information
		define('FB_APP_SECRET', 'abc123def456abc789def123abc456de');
		define('FB_PAGE_ID', '9876543219876543');
		define('FB_PAGE_USERNAME', 'phpfacebot');
		define('FB_ACCESS_TOKEN', 'EAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAEEAAESPCZDZDZDZDZDZDZDZDZDZDZDZDZDZDZDZDZDZD');
		define('FB_HUB_VERIFY_TOKEN', 'my_voice_is_my_password_and_some_more_text_to_make_it_very_long_and_unguessable');

		define('FB_PAGE_LINK', 'https://facebook.com/'.FB_PAGE_USERNAME);
		define('FB_MESSENGER_LINK', 'https://m.me/'.FB_PAGE_ID); // change to username if you have vanity url name

		define('MIXPANEL_TOKEN', '987abc654def321fed123cba456abc78'); // mixpanel token for tracking and stats
		define('WIT_AI_SERVER_TOKEN', 'EAGEAGEAGEAGEAGEAGEAGEAGEAGEAGEA');
		break;

	default: // a local config file that's part of .gitignore that doesn't get checked in
		@include(dirname(__FILE__).'/config.local.php');
		break;
}
