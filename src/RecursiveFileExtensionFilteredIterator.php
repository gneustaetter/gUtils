<?php
class RecursiveFileExtensionFilteredIterator extends FilterIterator {
	protected $extensions;

	public function __construct($path, array $extensions) {
		if(is_dir($path) && is_readable($path)) {
			if(count($extensions) < 1) {
				throw new InvalidArgumentException("One or more extensions must be passed in \$extensions array");
			}
			$this->extensions = $extensions;
			parent::__construct(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD));
		} else {
			throw new InvalidArgumentException("{$path} is not a valid path or is not readable");	
		}
	}

	public function accept() {
		return ($this->current()->isFile() && in_array(pathinfo($this->current()->getFilename(), PATHINFO_EXTENSION), $this->extensions));
	}
}