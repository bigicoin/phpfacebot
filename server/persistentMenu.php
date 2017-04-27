<?php

require_once(dirname( __FILE__ ) . '/environments.php');
require_once(dirname( __FILE__ ) . '/../CustomPayload.php');
require_once(dirname( __FILE__ ) . '/../FBMessengerProfile.php');
require_once(dirname( __FILE__ ) . '/../FBMenuItem.php');
require_once(dirname( __FILE__ ) . '/../Config.php');

$menuItems = array(new FBMenuItemUrl(), new FBMenuItemPostback(), new FBMenuItemNested());
$nested = array(new FBMenuItemUrl(), new FBMenuItemPostback());
$result = FBMessengerProfile::persistentMenu(array(
	$menuItems[0]->setTitle('Visit site')->setUrl('https://phpfacebot.com')->setWebviewHeightRatio('tall')->setWebviewShareButtonHidden(),
	$menuItems[1]->setTitle('Menu Button')->setPayload(CustomPayload::create('menuButton', array('name' => 'value'))),
	$menuItems[2]->setTitle('Nested Menu')->setCallToActions(array(
		$nested[0]->setTitle('Nested Visit Site')->setUrl('https://phpfacebot.com')->setWebviewHeightRatio('compact'),
		$nested[1]->setTitle('Nested Menu Button')->setPayload(CustomPayload::create('menuButton', array('name' => 'value')))
	))
), $disableComposer = true);

print_r($result);
