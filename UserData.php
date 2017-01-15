<?php

require_once(dirname( __FILE__ ) . '/Config.php');
require_once(dirname( __FILE__ ) . '/Redis.php');
require_once(dirname( __FILE__ ) . '/RedisNull.php');
require_once(dirname( __FILE__ ) . '/FBProfile.php');

class UserData {
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

	/**
	 * Use this to init a UserData object with the user id. (facebook user id)
	 * Example:
	 * $user = UserData::init(9123416123);
	 */
	public static function init($userId) {
		return new UserData($userId);
	}

	/**
	 * Internal variables for the UserData object
	 */
	private $id = -1;
	private $dataCache = array();

	/**
	 * Constructor
	 */
	public function __construct($userId) {
		self::connect();
		$this->id = intval($userId);
	}

	/**
	 * Setter methods -- usually saves directly to Redis data store.
	 */

	public function saveLastQuickReplies($jsonVal) {
		$this->saveUserData('last_quick_replies', $jsonVal);
		return $this;
	}

	public function setFirstName($val) {
		$this->saveUserData('first_name', $val);
		return $this;
	}

	public function setLastName($val) {
		$this->saveUserData('last_name', $val);
		return $this;
	}

	public function setProfilePic($val) {
		$this->saveUserData('profile_pic', $val);
		return $this;
	}

	public function setLocale($val) {
		$this->saveUserData('locale', $val);
		return $this;
	}

	public function setTimezone($val) {
		$this->saveUserData('timezone', $val);
		return $this;
	}

	public function setGender($val) {
		$this->saveUserData('gender', $val);
		return $this;
	}

	public function setIsPaymentEnabled($val) {
		$this->saveUserData('is_payment_enabled', $val);
		return $this;
	}

	private function saveUserData($fieldName, $value) {
		$this->dataCache[$fieldName] = $value;
		return self::$redis->hset(REDIS_NAMESPACE_PREFIX.'userdata_'.$fieldName, $this->id, $value);
	}

	/**
	 * Getter methods -- either gets from Redis or if previously retrieved, from local cache
	 */

	public function getId() { return $this->id; }

	public function getLastQuickReplies() {
		return $this->fetchCacheThenFetchDb('last_quick_replies');
	}

	private function fetchCacheThenFetchDb($userDataField) {
		if (empty($this->dataCache[$userDataField])) {
			// if not in local cache, find it in Redis DB
			$this->dataCache[$userDataField] = self::$redis->hget(REDIS_NAMESPACE_PREFIX.'userdata_'.$userDataField, $this->id);
		}
		return $this->dataCache[$userDataField];
	}

	public function getFirstName() {
		return $this->fetchCacheThenQuery('first_name');
	}
	
	public function getLastName() {
		return $this->fetchCacheThenQuery('last_name');
	}

	public function getProfilePic() {
		return $this->fetchCacheThenQuery('profile_pic');
	}

	public function getLocale() {
		return $this->fetchCacheThenQuery('locale');
	}

	public function getTimezone() {
		return $this->fetchCacheThenQuery('timezone');
	}

	public function getGender() {
		return $this->fetchCacheThenQuery('gender');
	}

	public function getIsPaymentEnabled() {
		return $this->fetchCacheThenQuery('is_payment_enabled');
	}

	private function fetchCacheThenQuery($userDataField) {
		if (empty($this->dataCache[$userDataField])) {
			// if not in local cache, find it in Redis DB
			$this->dataCache[$userDataField] = self::$redis->hget(REDIS_NAMESPACE_PREFIX.'userdata_'.$userDataField, $this->id);
			if (empty($this->dataCache[$userDataField])) {
				// if no name in DB either, we need to query for it from FB
				$this->queryFbProfile();
			}
		}
		return $this->dataCache[$userDataField];
	}
	
	private function queryFbProfile() {
		$profile = FBProfile::get($this->id, array('first_name', 'last_name', 'profile_pic', 'locale', 'timezone', 'gender', 'is_payment_enabled'));
		if (!empty($profile)) {
			$this->setFirstName($profile['first_name'])->setLastName($profile['last_name'])->setProfilePic($profile['profile_pic']);
			$this->setLocale($profile['locale'])->setTimezone($profile['timezone'])->setGender($profile['gender'])->setIsPaymentEnabled($profile['is_payment_enabled']);
		}
	}

	public function getNameIdCombo() {
		$firstname = $this->getName();
		$lastname = $this->getLastname();
		// only keep english letters in names for urls
		$firstname = preg_replace('/[^A-Za-z]*/', '', $firstname);
		$lastname = preg_replace('/[^A-Za-z]*/', '', $lastname);
		return $firstname.'-'.$lastname.'-'.$this->id;
	}

	public function getFriendCode() {
		return self::toLargeBase($this->id);
	}

	/**
	 * These static methods are usually aggregated features.
	 */

	public static function doesUserExist($userId) {
		self::connect();
		$result = self::$redis->sismember(REDIS_NAMESPACE_PREFIX.'userlist', $userId);
		return ($result == 1);
	}

	public static function recordUser($userId) {
		self::connect();
		return self::$redis->sadd(REDIS_NAMESPACE_PREFIX.'userlist', $userId);
	}

	public static function isValidFriendCode($friendCode) {
		return (preg_match('/^f_[A-Za-z0-9]+$/', $friendCode) == 1);
	}

	public static function getIdFromFriendCode($friendCode) {
		$convertedId = str_replace('f_', '', $friendCode);
		return self::toBase10($convertedId);
	}

	public static function removeUser($userId)
	{
		if (self::doesUserExist($userId)) {
			self::connect();
			self::$redis->srem(REDIS_NAMESPACE_PREFIX.'userlist', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_last_quick_replies', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_first_name', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_last_name', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_profile_pic', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_locale', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_timezone', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_gender', $userId);
			self::$redis->hdel(REDIS_NAMESPACE_PREFIX.'userdata_is_payment_enabled', $userId);
		}
	}
		
	/**
	 * Utility methods
	 */

	// for converting user ids to friend codes
	public static function toLargeBase($num) {
		$base = 'bcdfghjkmnpqrstwxzBCDFGHJKLMNPQRSTWXZ';
		// skip vowels (and y and v) to avoid creating cuss words.
		// also skip confusing letters like i, I, l, 1.
		$b = strlen($base);
		$r = $num  % $b ;
		$res = $base[$r];
		$q = floor($num/$b);
		while ($q) {
			$r = $q % $b;
			$q = floor($q/$b);
			$res = $base[$r].$res;
		}
		return $res;
	}

	// for converting friend codes back to user ids
	public static function toBase10($num) {
		$base='bcdfghjkmnpqrstwxzBCDFGHJKLMNPQRSTWXZ';
		$b = strlen($base);
		$limit = strlen($num);
		$res = strpos($base,$num[0]);
		for($i = 1; $i < $limit; $i++) {
			$res = $b * $res + strpos($base, $num[$i]);
		}
		return $res;
	}
}
