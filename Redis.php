<?php

/**
 * Functions in this file is mostly undocumented.
 * Because everything is really just a thin layer to a specific Redis command.
 * For details on each Redis command, it will be more useful to check out:
 * http://redis.io/commands
 */

class Redis {
	private $conn = null;

	/**
	 * Constructor opens a persistent connection to Redis server on a given host and port.
	 * Saved as an instance variable which gets used throughout the object's life.
	 *
	 * @return Redis
	 **/
	public function __construct($host, $port) {
		$this->conn = phpiredis_connect($host, $port); // use connect, not pconnect, with apache
		if (empty($this->conn)) {
			error_log('Failed to connect to redis at '.$host.':'.$port);
		}
	}

	/************************************************************************************************************************************
	 *
	 * Below are various Redis commands we currently use.
	 * See: http://redis.io/commands
	 *
	 ************************************************************************************************************************************/

	public function get($key) {
		return phpiredis_command_bs($this->conn, array('GET', strval($key)));
	}

	public function set($key, $val) {
		return phpiredis_command_bs($this->conn, array('SET', strval($key), strval($val)));
	}

	public function publish($key, $message) {
		return phpiredis_command_bs($this->conn, array('PUBLISH', strval($key), strval($message)));
	}

	public function hmset($key, $fieldsValues) {
		// this function is implemented with eval, no better way to do it.
		$command = '$result = phpiredis_command_bs($this->conn, array(\'HMSET\', strval($key)';
		$fields = array();
		$values = array();
		foreach ($fieldsValues as $field => $value) {
			$fields[] = strval($field);
			$values[] = strval($value);
		}
		for ($i = 0; $i < count($fields); $i++) {
			$command .= ', strval($fields['.$i.']), strval($values['.$i.'])';
		}
		$command .= '));';
		eval($command);
		return $result;
	}

	public function hset($key, $field, $value) {
		return phpiredis_command_bs($this->conn, array('hset', strval($key), strval($field), strval($value)));
	}

	public function hmget($key, $fields) {
		// this function is implemented with eval, no better way to do it.
		for ($i = 0; $i < count($fields); $i++) {
			$fields[$i] = strval($fields[$i]);
		}
		$command = '$result = phpiredis_command_bs($this->conn, array(\'HMGET\', strval($key)';
		for ($i = 0; $i < count($fields); $i++) {
			$command .= ', strval($fields['.$i.'])';
		}
		$command .= '));';
		eval($command);
		return $result;
	}

	public function hincrby($key, $field, $incrBy) {
		return phpiredis_command_bs($this->conn, array('hincrby', strval($key), strval($field), strval($incrBy)));
	}

	public function hget($key, $field) {
		return phpiredis_command_bs($this->conn, array('hget', strval($key), strval($field)));
	}

	public function hgetall($key) {
		return phpiredis_command_bs($this->conn, array('hgetall', strval($key)));
	}

	public function hlen($key) {
		return phpiredis_command_bs($this->conn, array('hlen', strval($key)));
	}

	public function incr($key) {
		return phpiredis_command_bs($this->conn, array('INCR', strval($key)));
	}

	public function expire($key, $seconds) {
		return phpiredis_command_bs($this->conn, array('EXPIRE', strval($key), strval($seconds)));
	}

	public function hdel($key, $field) {
		return phpiredis_command_bs($this->conn, array('HDEL', strval($key), strval($field)));
	}

	public function del($key) {
		return phpiredis_command_bs($this->conn, array('DEL', strval($key)));
	}

	// Note: redis < 2.4 supports only 1 value, where >= 2.4 supports unlimited values.
	// We just use 1 value here, so all redis versions can use this, as a simplified version.
	public function lpush($key, $value) {
		return phpiredis_command_bs($this->conn, array('LPUSH', strval($key), strval($value)));
	}

	public function lrem($key, $count, $value) {
		return phpiredis_command_bs($this->conn, array('LREM', strval($key), strval($count), strval($value)));
	}

	public function lrange($key, $start, $stop) {
		return phpiredis_command_bs($this->conn, array('LRANGE', strval($key), strval($start), strval($stop)));
	}

	public function brpop($key) {
		return phpiredis_command_bs($this->conn, array('BRPOP', strval($key), '0'));
	}

	public function rpop($key) {
		return phpiredis_command_bs($this->conn, array('RPOP', strval($key)));
	}

	public function llen($key) {
		return phpiredis_command_bs($this->conn, array('LLEN', strval($key)));
	}

	public function sadd($key, $member) {
		return phpiredis_command_bs($this->conn, array('SADD', strval($key), strval($member)));
	}

	public function srem($key, $member) {
		return phpiredis_command_bs($this->conn, array('SREM', strval($key), strval($member)));
        }

	public function sismember($key, $member) {
		return phpiredis_command_bs($this->conn, array('SISMEMBER', strval($key), strval($member)));
	}

	// Note: These new geo-spatial features are only available redis >= 3.2.
	public function geoadd($key, $member, $latitude, $longitude) {
		return phpiredis_command_bs($this->conn, array('GEOADD', strval($key), strval($longitude), strval($latitude), strval($member)));
	}

	public function georadius($key, $latitude, $longitude, $radius, $unit = 'mi', $withcoord = false, $withdist = false) {
		$params = array('GEORADIUS', strval($key), strval($longitude), strval($latitude), strval($radius), strval($unit));
		if ($withcoord) {
			$params[] = 'WITHCOORD';
		}
		if ($withdist) {
			$params[] = 'WITHDIST';
		}
		return phpiredis_command_bs($this->conn, $params);
	}
}
