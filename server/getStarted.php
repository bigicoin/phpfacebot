<?php

require_once(dirname( __FILE__ ) . '/environments.php');
require_once(dirname( __FILE__ ) . '/../CustomPayload.php');
require_once(dirname( __FILE__ ) . '/../FBSettings.php');
require_once(dirname( __FILE__ ) . '/../Config.php');

$result = FBSettings::getStarted(array('Get Started' => CustomPayload::create('getStartedButton', array('name' => 'value'))));

print_r($result);
