<?php

/**
 * This file works with the Config.php file in app root.
 * See the comments in that file for more details. In short, we use the SERVER_ADMIN config value
 * from Apache conf file to determine what environment the script will run in. (prod, dev, etc.)
 */

if (!empty($argv[1]) && $argv[1] == 'prod') {
	$_SERVER['SERVER_ADMIN'] = 'webmaster@yourdomain.com'; // prod
} else if (!empty($argv[1]) && $argv[1] == 'dev') {
	$_SERVER['SERVER_ADMIN'] = "user@yourdomain.com"; // dev
} else {
	$_SERVER['SERVER_ADMIN'] = "unknown"; // local sample config
}
