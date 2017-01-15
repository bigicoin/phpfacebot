<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title><?php echo BOT_NAME; ?>: Webview Test</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<link href="/bootstrap/css/bootstrap.min.css" type="text/css" rel="stylesheet" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
</head>
<body>

<script>

(function(d, s, id){
	var js, fjs = d.getElementsByTagName(s)[0];
	if (d.getElementById(id)) {return;}
	js = d.createElement(s); js.id = id;
	js.src = "//connect.facebook.com/en_US/messenger.Extensions.js";
	fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'Messenger'));

window.extAsyncInit = function() {
	// the Messenger Extensions JS SDK is done loading 
	MessengerExtensions.getUserID(function success(uids) {
		var psid = uids.psid;
		$('#userId').html('Your user id: '+psid);
	}, function error(err) {
	});

	$('#closeButton').click(function() {
		MessengerExtensions.requestCloseBrowser(function success() {}, function error(err) {});
	});
};

</script>

<div>
	Hi! This is a webview.
	<div id="userId"></div>
	<a href="#" id="closeButton">Close</a>
</div>

</body>
</html>
