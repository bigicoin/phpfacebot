<?php

/**
 * Copy every method signature from Redis class but do no-op for everything.
 */

class RedisNull {
	/**
	 * Constructor opens a persistent connection to Redis server on a given host and port.
	 * Saved as an instance variable which gets used throughout the object's life.
	 *
	 * @return Redis
	 **/
	public function __construct($host, $port) {
	}

	/************************************************************************************************************************************
	 *
	 * Below are various Redis commands we currently use.
	 * See: http://redis.io/commands
	 *
	 ************************************************************************************************************************************/

	public function get($key) {
		return null;
	}

	public function set($key, $val) {
		return null;
	}

	public function publish($key, $message) {
		return null;
	}

	public function hmset($key, $fieldsValues) {
		return null;
	}

	public function hset($key, $field, $value) {
		return null;
	}

	public function hmget($key, $fields) {
		return null;
	}

	public function hincrby($key, $field, $incrBy) {
		return null;
	}

	public function hget($key, $field) {
		return null;
	}

	public function hgetall($key) {
		return null;
	}

	public function hlen($key) {
		return null;
	}

	public function incr($key) {
		return null;
	}

	public function expire($key, $seconds) {
		return null;
	}

	public function hdel($key, $field) {
		return null;
	}

	public function del($key) {
		return null;
	}

	// Note: redis < 2.4 supports only 1 value, where >= 2.4 supports unlimited values.
	// We just use 1 value here, so all redis versions can use this, as a simplified version.
	public function lpush($key, $value) {
		return null;
	}

	public function lrem($key, $count, $value) {
		return null;
	}

	public function lrange($key, $start, $stop) {
		return null;
	}

	public function brpop($key) {
		return null;
	}

	public function rpop($key) {
		return null;
	}

	public function llen($key) {
		return null;
	}

	public function sadd($key, $member) {
		return null;
	}

	public function srem($key, $member) {
		return null;
	}

	public function sismember($key, $member) {
		return null;
	}

	// Note: These new geo-spatial features are only available redis >= 3.2.
	public function geoadd($key, $member, $latitude, $longitude) {
		return null;
	}

	public function georadius($key, $latitude, $longitude, $radius, $unit = 'mi', $withcoord = false, $withdist = false) {
		return null;
	}
}
