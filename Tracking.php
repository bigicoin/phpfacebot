<?php

require_once(dirname( __FILE__ ) . '/Config.php');
require_once(dirname( __FILE__ ) . '/Redis.php');
require_once(dirname( __FILE__ ) . '/RedisNull.php');

class Tracking {
	private static $redis = null;

	/**
	 * Establish connection to Redis (main data store) server.
	 * Only makes a connection once in a request, and keep it.
	 */
	private static function connect() {
		// init connection to redis if not already
		if (empty(self::$redis)) {
			if (REDIS_ENABLED == 1) {
				self::$redis = new Redis(REDIS_HOST, REDIS_PORT);
			} else {
				self::$redis = new RedisNull(REDIS_HOST, REDIS_PORT);
			}
		}
	}

	public static function track($eventName, $eventParams) {
		self::connect();

		if (empty($eventParams)) {
			$eventParams = array();
		} else if (!is_array($eventParams)) {
			$eventParams = array();
		}

		if (!empty($eventParams['user_id'])) {
			$eventParams['distinct_id'] = $eventParams['user_id'];
		}

		$event = array(
			'name' => strval($eventName),
			'params' => $eventParams
		);

		self::$redis->lpush(REDIS_NAMESPACE_PREFIX.REDIS_TRACK_QUEUE_NAME, json_encode($event));
	}
}
