<?php

/**
 * A basic generic job daemon, originally written by PHP doc commenter duerra at yahoo dot com,
 * on http://www.php.net/manual/en/function.pcntl-wait.php .
 * Cleaned up and re-organized as a basic job daemon we can use for any queue consumer.
 *
 * To extend this class and make a daemon that does useful work, override:
 * - parentProcess() method for handling any parent logic (dequeuing entries from a queue server, etc.)
 * - childProcess() method for the child process's work
 * Pass arguments from the parent to the child using $this->launchJob($args). Use arrays or objects
 * as argument to pass all the data you need.
 *
 * To run, construct an object of the class, make any set*() calls necessary to setup, then just call run().
 */
class JobDaemon {

	const OUTPUT_STDOUT = 'php://stdout';
	const OUTPUT_STDERR = 'php://stderr';

	const LEVEL_NOTICE = 1;
	const LEVEL_WARNING = 2;
	const LEVEL_ERROR = 3;

	protected $maxChildren = 25;
	protected $activeChildren = 0;
	protected $currentJobs = array(); // this is keyed by pid (process id), and value is either a callback array($obj, 'func'), or true if no callback
	protected $signalQueue = array();
	protected $parentPID;

	private $outputChannel = null;
	private $outputHandler = null;
	private $outputLevel = self::LEVEL_NOTICE;
	
	/**
	 * Construct the daemon object
	 */
	public function __construct() {
		$this->parentPID = getmypid();
		pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));
	}

	/**
	 * Sets the output method and level. You may use the consts OUTPUT_STDOUT or OUTPUT_STDERR,
	 * for those respective output channels, or path to an output file.
	 *
	 * @param	string	Use stdout, stderr or an output log file.
	 * @param	int		Use LEVEL_NOTICE, LEVEL_WARNING or LEVEL_ERROR, from most verbose to least verbose.
	 */
	public function setOutput($outputChannel, $outputLevel = self::LEVEL_NOTICE) {
		$this->outputChannel = $outputChannel;
		$this->outputLevel = $outputLevel;
	}

	/**
	 * Sets the number of max children processes allowable in this daemon. Default is 25.
	 */
	public function setMaxChildren($max) {
		$this->maxChildren = $max;
	}

	/**
	 * [Override this]
	 * The parent process logic for this daemon. Use for connecting to any data store, dequeue work (for queue consumers),
	 * instantiating objects for children to use, etc.
	 */
	protected function parentProcess() {
		$this->log("[Override JobDaemon::parentProcess() in your class for your parent process logic that assigns work to child processes!]\n");
		for ($i = 0; $i < 100; $i++) {
			$launched = $this->launchJob(null);
			if (!$launched) {
				throw new Exception("Could not fork a new process. Killing parent process.");
			}
		}
	}

	/**
	 * [Override this]
	 * The child process logic for the heavy lifting work. In a queue consumer this would be processing a queue entry.
	 * Make REST API calls, DB calls, etc.
	 *
	 * @param	object	The argument object passed from the parent process via launchJob() call.
	 */
	protected function childProcess($args) {
		$this->log("[Override JobDaemon::childProcess() in your class in order to do useful work here!]\n");
		usleep(100000); // sleep for 100ms
	}

	/**
	 * Logs a message to the output channel.
	 */
	protected function log($msg, $level = self::LEVEL_ERROR) {
		if (empty($this->outputHandler)) {
			if (empty($this->outputChannel)) {
				$this->outputChannel = self::OUTPUT_STDOUT;
				echo "(Output channel not set, using STDOUT.)\n";
			}
			$this->outputHandler = fopen($this->outputChannel, 'a'); // always append
			if (empty($this->outputHandler)) {
				echo "Failed to open output channel (".$this->outputChannel.")! Ending daemon.\n";
			}
		}
		if ($level >= $this->outputLevel) {
			fwrite($this->outputHandler, date('[Y-m-d H:i:s] ') . $msg);
		}
	}
	
	/**
	 * Run the Daemon
	 */
	public function run(){
		$this->log("Initializing daemon...\n");
		$this->parentProcess();
		
		// Wait for child processes to finish before exiting JobDaemon
		while(count($this->currentJobs)){
			$this->log("Waiting for all child processes to finish...\n");
			sleep(1); // sleep for 1s
		}
	}
	
	/**
	 * Launch a job from the job queue
	 */
	protected function launchJob($args, $callback = null){
		while ($this->activeChildren >= $this->maxChildren) {
			$this->log("Max reached. Waiting for free-ups...\n", self::LEVEL_NOTICE);
			usleep(500000); // sleep for 500ms (0.5s)
		}
		$this->activeChildren++;
		$pid = pcntl_fork();
		if($pid == -1){
			// Problem launching the job, quits out
			$this->log('Could not launch new job, exiting');
			$this->activeChildren--;
			return false;
		}
		else if ($pid){
			// Parent process
			// Sometimes you can receive a signal to the childSignalHandler function before this code executes if
			// the child script executes quickly enough!
			if (empty($callback)) {
				$this->currentJobs[$pid] = true;
			} else {
				$this->currentJobs[$pid] = $callback;
			}
			
			// In the event that a signal for this pid was caught before we get here, it will be in our signalQueue array
			// So let's go ahead and process it now as if we'd just received the signal
			if(isset($this->signalQueue[$pid])){
				$this->log("Found $pid in the signal queue, processing it now \n", self::LEVEL_NOTICE);
				$this->childSignalHandler(SIGCHLD, $pid, $this->signalQueue[$pid]);
				unset($this->signalQueue[$pid]);
			}
		}
		else{
			// Forked child
			$exitStatus = 0; // Error code if we want to set it differently
			$this->childProcess($args);
			exit($exitStatus);
		}
		return true;
	}
	
	/**
	 * The signal handler for children processes exitting and sending SIGCHLD to parent process.
	 */
	public function childSignalHandler($signo, $pid = null, $status = null){
		// because of unreliability of SIGCHLD signals (if two signals triggered too closely, only one
		// of these calls to the handler is done), every time we receive a signal we just loop
		// through all available child pids, then also exhaust any other signals by doing a -1 to see if
		// any other ones are there
		foreach ($this->currentJobs as $pid => $jobCallback) {
			$caughtPid = pcntl_waitpid($pid, $status, WNOHANG);
			if ($caughtPid > 0) {
				// this child exitted!
				$exitCode = pcntl_wexitstatus($status);
				if ($exitCode != 0) {
					$this->log("$caughtPid exited with status ".$exitCode."\n", self::LEVEL_NOTICE);
				}
				// make end of child callback before we call it a done job
				if ($this->currentJobs[$caughtPid] !== true) {
					$this->log("Calling end callback for $caughtPid\n", self::LEVEL_NOTICE);
					call_user_func($this->currentJobs[$caughtPid]);
				}
				unset($this->currentJobs[$caughtPid]);
				$this->activeChildren--;
			}
		}

		while (true) {
			$pid = pcntl_waitpid(-1, $status, WNOHANG);
			if ($pid <= 0) {
				break;
			}
			if ($pid && isset($this->currentJobs[$pid])) {
				$exitCode = pcntl_wexitstatus($status);
				if ($exitCode != 0) {
					$this->log("$pid exited with status ".$exitCode."\n", self::LEVEL_NOTICE);
				}
				// make end of child callback before we call it a done job
				if ($this->currentJobs[$pid] !== true) {
					$this->log("Calling end callback for $pid\n", self::LEVEL_NOTICE);
					call_user_func($this->currentJobs[$pid]);
				}
				unset($this->currentJobs[$pid]);
				$this->activeChildren--;
			}
			else if ($pid) {
				//Oh no, our job has finished before this parent process could even note that it had been launched!
				//Let's make note of it and handle it when the parent process is ready for it
				$this->log("..... Adding $pid to the signal queue ..... \n", self::LEVEL_NOTICE);
				$this->signalQueue[$pid] = $status;
			}
		}

		return true;
	}
}
