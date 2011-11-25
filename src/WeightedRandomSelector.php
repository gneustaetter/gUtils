<?php
/*
   gutils WeightedRandomSelector
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

class WeightedRandomSelector {
	protected $totalWeight = 0;
	public $defaultWeight = 1;
	protected $items = array();
	protected $weights = array();

	public function __construct($items = NULL) {
		if(is_array($items) && count($items) > 0) {
			$this->addItems($items);
		}
	}

	public function addItem($item, $weight=false) {
		$this->items[] = $item;
		$weight = ($weight) ? $weight : $this->defaultWeight;
		if(!is_int($weight) || $weight < 1) {
			throw new Exception("Weight must be a positive integer");
		}
		$this->totalWeight += $weight;
		$this->weights[] = $weight;
	}

	public function addItems($items) {
		if(!is_array($items) || count($items) < 1) {
			throw new Exception("Items must be an array of items");
		}
		foreach($items as $item) {
			if(!is_array($item)) {
				$this->addItem($item);
			} elseif(is_array($item) && count($item) == 2) {
				$this->addItem($item[0],$item[1]);
			} else {
				throw new Exception("Each item must either be a string or an array with two elements, the value and the weight");
			}
		}
	}

	public function get() {
		if($this->totalWeight == 0) {
			throw new Exception("You must add items before trying to get a value");
		}
		$rand = mt_rand(1,$this->totalWeight);
		foreach($this->weights as $key=>$weight) {
			$rand -= $weight;
			if($rand < 1) {
				return $this->items[$key];
			}
		}
	}

	public function getMulti($count) {
		if(!is_int($count) || $count < 1) {
			throw new Exception("Count must be a positive integer");
		}
		$results = array();
		for($i=0;$i<$count;$i++) {
			$results[] = $this->get();
		}
		return $results;
	}
}