<?php
/*
   gutils Console
   Copyright 2011 Greg Neustaetter

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

class Console {
	
	protected $mode;
	protected $lastLog;
	protected $timers = array();
	protected $mainHelpText = '';
	protected $argDefs = array();
	public $args = array();
	
	const OPTIONAL_VALUE = '::';
	const REQUIRED_VALUE = ':';
	const NO_VALUE = '';

	public function __construct(array $argDefs = array(), $helpText = '') {
		$this->mode = (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) ? 'cli' : 'browser';
		if($this->mode != 'cli') {
			echo '<html><head><title>Script Output</title></head><body><pre>';
		}
		$this->lastLog = microtime(true);
		$this->timerStart('__scriptbegin__');
		$argDefs[] = array(
			'name' => 'help',
			'type' => Console::NO_VALUE,
			'help' => "Shows help for this script"
		);
		$this->mainHelpText = $helpText;
		$this->args = $this->parseArgs($argDefs);
	}

	public function isCLI() {
		return $this->mode == 'cli';
	}

	protected function parseArgs($argDefs) {
		$args = array();
		$shortArgs = '';
		$longArgs = array();
		$defaults = array(
			'name' => NULL,
			'default' => NULL,
			'forceLong' => false,
			'type' => Console::OPTIONAL_VALUE,
			'help' => 'No help available',
			'required' => false,
			'possibleValues' => NULL,
			'validator' => NULL,
			'validationMsg' => NULL
		);
		foreach($argDefs as $arg) {
			$arg = array_merge($defaults, $arg);
			$getOptName = $arg['name'] . $arg['type'];
			if((strlen($arg['name']) == 1) && !$arg['forceLong']) {
				$shortArgs .= $getOptName;
				$arg['short'] = true;
			} else {
				$longArgs[] = $getOptName;
				$arg['short'] = false;
			}
			$args[$arg['name']] = $arg;
		}
		$this->argDefs = $args;
		$vals = ($this->isCLI()) ? getopt($shortArgs,$longArgs) : $_GET;
		if(array_key_exists('help',$vals)) {
			$this->showHelp();
			exit;
		}
		foreach($args as $arg) {
			$name = $arg['name'];
			if(!array_key_exists($name,$vals)) {
				if($arg['required']) {
					$this->log("Option {$this->getPrefixedArgName($name)} is required", false);
					exit;
				}
				if(isset($arg['default'])) {
					$vals[$name] = $arg['default'];
				}
			}
			if(array_key_exists($name,$vals)) {
				if(in_array($arg['type'],array(Console::NO_VALUE, Console::OPTIONAL_VALUE)) && ($vals[$name] === false)) {
					$vals[$name] = 1;
				} elseif(($arg['type'] == Console::NO_VALUE) && is_array($vals[$name])) {
					$vals[$name] = count($vals[$name]);
				}
				if(isset($arg['possibleValues'])) {
					if(!in_array($vals[$name], $arg['possibleValues'])) {
						$possibleVals = implode(', ',$arg['possibleValues']);
						$this->log("Option {$this->getPrefixedArgName($name)} must be one of the following values: $possibleVals", false);
						exit;
					}
				}
				if(isset($arg['validator'])) {
					if(!call_user_func($arg['validator'], $vals[$name])) {
						$this->log("Option {$this->getPrefixedArgName($name)} failed validation: {$arg['validationMsg']}", false);
						exit;
					}
				}
			}
		}
		return $vals;
	}

	public function showHelp() {
		$this->log("\nSUMMARY", false);
		$this->log("   " . $this->mainHelpText, false);
		if(count($this->argDefs) > 1) {
			$this->log("\nOPTIONS", false);
			foreach($this->argDefs as $arg) {
				$prefix = ($arg['short']) ? '-' : '--';
				$required = ($arg['required']) ? " [REQUIRED]" : " [OPTIONAL]";
				switch($arg['type']) {
					case Console::NO_VALUE:
						$suffix = $required;
						break;
					case Console::REQUIRED_VALUE:
						$suffix = ' value' . $required;
						break;
					case Console::OPTIONAL_VALUE:
						$suffix = '=optional_value' . $required;
						break;
				}
				$this->log("   {$prefix}{$arg['name']}{$suffix}", false);
				$this->log("       {$arg['help']}\n", false);
			}
		}
	}

	public function getArg($name) {
		if($this->isArgSet($name)) {
			return $this->args[$name];
		}
		return false;
	}

	protected function getArgDefByName($name) {
		foreach($this->argDefs as $argDef) {
			if($argDef['name'] == $name) {
				return $argDef;
			}
		}
		throw new Exception("No option with name {$name} exists");
	}

	protected function getPrefixedArgName($name) {
		$arg = $this->getArgDefByName($name);
		$prefix = ($arg['short']) ? '-' : '--';
		return $prefix . $name;
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
		$duration = number_format($this->timerStop('__scriptbegin__'),4);
		if($showDuration) {
			$this->log("Completed in {$duration} seconds");
		}
		if(!$this->isCLI()) {
			echo '</pre></body></html>';
		}
	}
}