<?php
/*
gutils Console
Licensed under the New BSD License, as follows:

Copyright (c) 2011, Greg Neustaetter
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the <organization> nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class Console {
	
	protected $mode;
	protected $lastLog;
	protected $timers = array();
	public $args = array();

	public function __construct($shortopts = NULL, array $longopts = array(), $defaults = array()) {
		$this->mode = (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) ? 'cli' : 'browser';
		if($this->mode != 'cli') {
			echo '<html><head><title>Script Output</title></head><body><pre>';
		}
		$this->lastLog = microtime(true);
		$this->timerStart('__scriptbegin__');
		$longopts[] = 'help::';
		$options = ($this->mode == 'cli') ? getopt($shortopts,$longopts) : $_GET;
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
			$this->log("Completed in {$duration} seconds");
		}
		if(!$this->isCLI()) {
			echo '</pre></body></html>';
		}
	}
}