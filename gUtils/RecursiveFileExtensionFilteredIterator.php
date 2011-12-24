<?php
namespace gUtils;

/*
   gutils RecursiveFileExtensionFilteredIterator
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

class RecursiveFileExtensionFilteredIterator extends \FilterIterator {
	protected $extensions;

	public function __construct($path, array $extensions) {
		if(is_dir($path) && is_readable($path)) {
			if(count($extensions) < 1) {
				throw new \InvalidArgumentException("One or more extensions must be passed in \$extensions array");
			}
			$this->extensions = $extensions;
			parent::__construct(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD));
		} else {
			throw new \InvalidArgumentException("{$path} is not a valid path or is not readable");	
		}
	}

	public function accept() {
		return ($this->current()->isFile() && in_array(pathinfo($this->current()->getFilename(), PATHINFO_EXTENSION), $this->extensions));
	}
}