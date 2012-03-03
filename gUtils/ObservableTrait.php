<?php
namespace gUtils;

/*
   gutils ObservableTrait
   Copyright 2012 Greg Neustaetter

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

trait ObservableTrait {

	protected $observableProps = [
		'observers' => [],
		'observing' => true,
		'counter' => 0
	];

	/**
	 * Adds a new event listener
	 * @param string $name the event name to add a listener for
	 * @param Callable $handler a callable function (anything that works with call_user_func_array) like a closure, function name, or instance method
	 * @return array an array with two elements, the event name and the id of the listener.  Used as the input to removeListener
	 */
	public function addListener($name, Callable $handler) {
		$counter = ++$this->observableProps['counter'];
		if(!isset($this->observableProps['observers'][$name])) {
			$this->observableProps['observers'][$name] = [];
		}
		$this->observableProps['observers'][$name][$counter] = $handler;
		return [$name, $counter];
	}

	/**
	 * Add multiple event listeners in a single call
	 * @param array $listeners associative array with keys as even names and values as handler functions accpeted by addListener
	 * @return array array of addListener responses for use in removing listeners
	 */
	public function addListeners(array $listeners) {
		$ids = [];
		foreach($listeners as $name=>$handler) {
			$ids[] = $this->addListener($name, $handler);
		}
		return $ids;
	}

	/**
	 * Removes an event listener
	 * @param  array $listenerId the return value from addListener
	 * @return boolean returns true if the event is removed, otherwise throws \Exception
	 */
	public function removeListener(array $listenerId) {
		$name = $listenerId[0];
		$id = $listenerId[1];
		if(isset($this->observableProps['observers'][$name][$id])) {
			unset($this->observableProps['observers'][$name][$id]);
			return true;
		}
		throw new \Exception("Observer doesn't exist");
	}

	/**
	 * Removes multiple event listeners
	 * @param  array  $listenerIds an array of event listener ids as returned by addListeners
	 * @return boolean returns true if the events are removed, otherwise throws \Exception
	 */
	public function removeListeners(array $listenerIds) {
		foreach($listenerIds as $listenerId) {
			$this->removeListener($listenerId);
		}
		return true;
	}

	/**
	 * Fires an event and notifies observers.  Observers will be passed all arguments passed to fireEvent except for the first
	 * @param string $name the event name
	 * @param mixed $arg zero or more additional parameters that will be passed to listeners
	 * @return boolean returns false if any listeners return false, otherwise returns true
	 */
	public function fireEvent($name) {
		$response = true;
		if($this->observableProps['observing']) {
			if(isset($this->observableProps['observers'][$name]) && is_array($this->observableProps['observers'][$name])) {
				$args = func_get_args();
				array_shift($args);
				foreach($this->observableProps['observers'][$name] as $id=>$observer) {
					$return = call_user_func_array($observer,$args);
					if($return === false) {
						$response = false;
					}
				}
			}
		}
		return $response;
	}

	/**
	 * Prevents listeners from being notified when events are fired
	 * @return void
	 */
	public function stopEvents() {
		$this->observableProps['observing'] = false;
	}

	/**
	 * Called after stopEvents to resume event notification to listeners
	 * @return void
	 */
	public function resumeEvents() {
		$this->observableProps['observing'] = true;
	}

	/**
	 * Removes all event listeners
	 * @return void
	 */
	public function clearListeners() {
		$this->observableProps['observers'] = [];
	}
}