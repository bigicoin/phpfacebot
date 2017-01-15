PHPFaceBot
==========

PHPFaceBot is a framework that provides all the groundwork to create a Facebook Messenger Bot app in PHP.

To use this, you'll need to have a web server running Apache, with PHP 5.3+. See system requirements below for more details.

To set up a Facebook Messenger Bot now, see the instructions below.

Facebook Messenger Bot setup quick guide
========================================

Follow this [documentation](https://developers.facebook.com/docs/messenger-platform/quickstart).

Brief steps: (after completing server setup with working Apache, see below)

1.	Create [app](https://developers.facebook.com/quickstarts/?platform=web) and [page](https://www.facebook.com/pages/create).
1.	Go to the app's App Dashboard, Add Product -> Messenger.
1.	Setup webhooks.
1.	Check fields `message_deliveries`, `messages`, `messaging_optins`, and `messaging_postbacks`. Also use `messaging_referrals` for getting ref parameters.
1.	Get Page Access token.
1.	Subscribe App to Page.
1.	Test.

Setting up with Apache
======================

1.	Git clone this repo in your home directory.
1.	Use the example apache config file, edit accordingly, put in your Apache vhost or conf.d directory.
1.	Run `letsencrypt` to get SSL certs - this is required, Facebook wants all webhooks to have SSL.
1.	(Alternatively, use Cloudflare's free plan and get SSL that way, but you need to wait 24 hours for the cert)
1.	Verify your setup by visiting your domain on browser.

Supported Messenger API features
================================

*	Webhook
	*	Receiving messages
		*	Text
		*	Image
		*	Sticker
		*	Location
		*	Quick Reply
	*	Receiving postbacks
	*	Receiving deliveries
	*	Receiving optins
*	Send API
	*	Text
	*	Image as url
	*	Image as attachment
	*	Quick replies
	*	Templates
		*	Button (text with buttons)
		*	Generic (carousel display)
		*	List (vertical list display)
	*	Buttons
		*	URL
		*	Postback
		*	Share
		*	Buy
	*	Sender actions
		*	Mark as seen
		*	Typing on
*	Webview and Extensions
*	Plugin
	*	Send to messenger (opt-in)
	*	Message Us
*	Thread Settings
	*	Greeting text
	*	Get Started button
	*	Persistent menu
	*	Domain whitelisting
*	User profile API
	*	first_name, last_name, profile_pic, locale, timezone, gender, is_payment_enabled

The framework also handles:

*	Security
	*	Properly validates a webhook request using the X-Hub-Signature that Facebook provides, to match
		against the hashed payload using Facebook app secret.
*	Batching
	*	High traffic volume bots can get messages in a batch. A request can contain multiple `entry`s,
		and each `entry` can contain multiple `messaging`s. This framework handles both properly.
*	Wit.ai NLP
	*	Contains a simple Wit API wrapper to allow you to send text messages to wit.ai's intent parsing
		service to parse a natural language text if needed.
*	Analytics
	*	Contains the Mixpanel API wrapper along with a queue and consumer setup, to allow for tracking
		any number of events to a Mixpanel account.

Unsupported Messenger API features
==================================

*	Webhook
	* Receiving checkout updates
	* Receiving payments
	* Receiving account linkings
*	Subscribe bot to page (can just use their web page UI for this)
*	Send API
	*	Audio as attachment
	*	Video as attachment
	*	File as attachment
	*	Templates
		*	Receipt
		*	Airline boarding pass
		*	Airline checkin
		*	Airline itinerary
		*	Airline flight update
	*	Buttons
		*	Call
		*	Log in, log out
	*	Sender actions
		*	Typing off
*	Plugin
	*	Checkbox
*	Thread Settings
	*	Account linking
	*	Payment settings

System requirements
===================

Required:

1.	PHP 5.3 or above
1.	Apache web server
1.	PHP module: [curl](http://php.net/manual/en/book.curl.php) - to make HTTP API requests
1.	PHP module: [json](http://php.net/manual/en/book.json.php) - to encode/decode data to and from API

Optional:

1.	[Redis](https://redis.io) - my choice of a data store (database). Optional because the demo bot can still run without it,
	just set REDIS_ENABLED to 0 in your config file. Some features will be disabled without it.
1.	[phpiredis](https://github.com/nrk/phpiredis) - my choice of a PHP Redis client. You can use a different Redis client
	(and modify the Redis.php wrapper code), or you can even use a different database.
1.	PHP module: [POSIX](http://php.net/manual/en/ref.posix.php) - only if you want to use the built-in queue-consumer
	setup for sending analytics data to Mixpanel.
1.	[APC](http://php.net/manual/en/book.apc.php) - my opcode cache of choice, which also provides user cache features.
	(If you use PHP 5.5+, you get OPcache, which accomplishes the same thing. Similar to PHP-FPM.)

Included PHP libraries in this framework
========================================

For documentation purposes, here is the list of PHP libraries that are included in this framework. I am including their code files
in this framework directly so it's not necessary to install composer or run composer updates when using this.

1.	[ToroPHP](https://github.com/anandkunal/ToroPHP) - quick, simple and robust URL routing.
1.	[PHPWit.ai](https://github.com/Udo/PHPWit.ai) - very simple PHP bindings for wit.ai API, for only the NLP (text processing) method.
1.	[Mixpanel PHP](https://github.com/mixpanel/mixpanel-php) - only needed if you want to send tracking data to Mixpanel analytics service.
1.	[phpJobDaemon](https://github.com/bigicoin/phpJobDaemon) - only needed if you want to use the queue consumer setup for Mixpanel
