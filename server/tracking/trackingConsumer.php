<?php

declare(ticks=1);

require_once(dirname( __FILE__ ) . '/../environments.php');
require_once(dirname( __FILE__ ) . '/JobDaemon.php');
require_once(dirname( __FILE__ ) . '/../../Config.php');
require_once(dirname( __FILE__ ) . '/../../Redis.php');
require_once(dirname( __FILE__ ) . '/mixpanel/Mixpanel.php');

class TrackingConsumer extends JobDaemon {
	/**
	 * Instance of the Redis connection object
	 */
	private $redis;

	/**
	 * Redis connection info
	 */
	private $redisHost = '';
	private $redisPort = 0;
	private $redisQueueName = '';

	/**
	 * Mixpanel token
	 */
	private $mixpanelToken;

	/**
	 * Sets the Redis connection info
	 */
	public function setRedisServer($host, $port, $queueName) {
		$this->redisHost = $host;
		$this->redisPort = $port;
		$this->redisQueueName = $queueName;
	}

	/**
	 * Sets Mixpanel keys
	 */
	public function setTrackParams($mixpanelToken) {
		$this->mixpanelToken = $mixpanelToken;
	}

	/**
	 * TrackingConsumer parentProcess.
	 * Instantiates the Mixpanel libraries.
	 * Connects to redis server, blocking-dequeues from the tracking queue, assigns each piece of track data to a child thread.
	 */
	protected function parentProcess() {
		$this->log("Starting track queue consumer...\n");
		$this->redis = new Redis($this->redisHost, $this->redisPort);
		if (empty($this->redis)) {
			throw new Exception("Could not connect to Redis host at \"".$this->redisHost.":".$this->redisPort."\"");
		}
		while (true) {
			$response = $this->redis->brpop($this->redisQueueName);
			// $response[0] is queue name, $response[1] is the data in it
			if (!empty($response)) {
				$entry = json_decode($response[1], true);
				if (!empty($entry) && !empty($entry['name'])) { // check if valid entry
					$childLaunched = $this->launchJob($entry);
					if (!$childLaunched) {
						$this->log("I/O Error: Could not fork child process! Continuing...\n");
					}
				} else {
					$this->log("Queue entry looks invalid, discarding.\n[Entry]:".$response[1]."\n");
				}
			} else {
				$this->log("Unknown error popping queue: \"".$this->redisQueueName."\". Continuing...\n");
			}
		}
	}

	/**
	 * TrackingConsumer childProcess.
	 * Takes the piece of track data, makes the REST API calls to Mixpanel with the data.
	 *
	 * @param	object	The argument object passed from the parent process via launchJob() call.
	 */
	protected function childProcess($args) {
		$mixpanel = Mixpanel::getInstance($this->mixpanelToken);
		$params = (!empty($args['params'])) ? $args['params'] : array();
		$mixpanel->track($args['name'], $params);
	}
}

$consumer = new TrackingConsumer();
$consumer->setRedisServer(REDIS_HOST, REDIS_PORT, REDIS_NAMESPACE_PREFIX.REDIS_TRACK_QUEUE_NAME);
$consumer->setTrackParams(MIXPANEL_TOKEN);
$consumer->setMaxChildren(100);
$consumer->setOutput(JobDaemon::OUTPUT_STDOUT, JobDaemon::LEVEL_WARNING);
$consumer->run();
