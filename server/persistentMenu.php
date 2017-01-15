<?php

require_once(dirname( __FILE__ ) . '/environments.php');
require_once(dirname( __FILE__ ) . '/../CustomPayload.php');
require_once(dirname( __FILE__ ) . '/../FBSettings.php');
require_once(dirname( __FILE__ ) . '/../Config.php');

$result = FBSettings::persistentMenu(array(
	'Visit Site' => 'https://phpfacebot.com',
	'Help' => CustomPayload::create('helpButton', array('name' => 'value'))
));

print_r($result);
