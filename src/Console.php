<?php
class Console {
	
	protected $mode;
	protected $lastLog;
	protected $timers = array();
	public $args = array();

	public function __construct($shortopts = NULL, array $longopts = array(), $defaults = array()) {
		$this->mode = (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) ? 'cli' : 'browser';
		if($this->mode != 'cli') {
			echo '<pre>';
		}
		$this->lastLog = microtime(true);
		$this->timerStart('__scriptbegin__');
		$longopts[] = 'help::';
		$options = ($this->mode == 'cli') ? getopt($shortopts,$longopts) : array();
		$this->args = array_merge($defaults, $options);
		if($this->isArgSet('help')) {
			$this->showHelp();
			$this->end(false);
		}
	}

	public function isCLI() {
		return $this->mode == 'cli';
	}

	public function showHelp() {
		if(file_exists('readme.txt')) {
			$this->log(file_get_contents('readme.txt'), false);
		} else {
			$this->log('No help is available for this script');
		}
	}

	public function getArg($name) {
		if($this->isArgSet($name)) {
			if(is_string($this->args[$name])) {
				return $this->args[$name];
			}
			return true;
		}
		return false;
	}

	public function isArgSet($name) {
		return array_key_exists($name,$this->args);
	}

	public function log($msg,$showTimeDiff = true) {
		if($showTimeDiff) {
			$time = microtime(true);
			$diff = number_format($time - $this->lastLog,2);
			$this->lastLog = $time;
			$msg = $msg . " [$diff]";
		}
		if($this->isCLI()) {
			echo $msg . "\n";
		} else {
			echo htmlentities($msg,ENT_QUOTES) . "\n";
			flush();
		}
		
	}

	public function generateReport($data, $template, $location) {
		ob_start();
		require($template);
		file_put_contents($location,ob_get_contents());
		ob_end_clean();
	}

	public function timerStart($name) {
		$this->timers[$name] = array('start' => microtime(true), 'end' => NULL, 'duration' => NULL);
	}

	public function timerStop($name) {
		$this->timers[$name]['end'] = microtime(true);
		$this->timers[$name]['duration'] = $this->timers[$name]['end'] - $this->timers[$name]['start'];
		return $this->timers[$name]['duration'];
	}

	public function getTimers() {
		return $this->timers;
	}

	public function end($showDuration = true) {
		$duration = $this->timerStop('__scriptbegin__');
		if($showDuration) {
			$this->log("Completed in $duration seconds");
		}
		if(!$this->isCLI()) {
			echo '</pre>';
		}
	}
}