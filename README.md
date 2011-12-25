gUtils
=======

A collection of standalone utilities for PHP developemnt.  These classes are namespaced and accordingly require PHP 5.3+.  Each utility is a single file that can easily be included into any project - use one, or use them all.  Utilities include:

* Console - utilities for command line scripitng with PHP
* InputValidator - input validation library using a fluent interface
* PasswordHelper - bcrypt password creation, comparison, and password strength validation
* RecursiveFileExtensionFilteredIterator - find all of the files with a specific extension in a file tree
* WeightedRandomSelector - get psuedo random values based on weights associated to a list of items

See below for more in depth descriptions and code samples

##Console  
Helper for writing command-line scripts, accepting arguments, printing status to the terminal, generating a --help command, and minimal support for calling the same scripts from a web browser.

```php
<?php
require('gUtils/Console.php');
use gUtils\Console;

$console = new Console(array(
  array(
		'name' => 'path',
		'type' => Console::REQUIRED_VALUE,
		'help' => 'path to the file',
		'validator' => function($val) {
			return (is_dir($val) && is_readable($val));
		},
		'validationMsg' => 'Path must be a readable directory'
	), array(
		'name' => 'type',
		'default' => 'full',
		'type' => Console::REQUIRED_VALUE,
		'help' => 'The type of run, either full or update',
		'possibleValues' => array('full','update')
	)

), "Lists all of the files in a directory");
$console->log("Starting listing for " . $console->getArg('path'));
// do work
$console->end();
```

##InputValidator   
An input validation and and transformation library using a fluent interface to reduce the amount of code needed to perform validation. [Read the documentation](https://github.com/gneustaetter/gUtils/wiki/InputValidator-Documentation)

```php
<?
require('gUtils/InputValidator.php');
// this could be from $_POST
$data = array(
  'name' => 'Greg Neustaetter',
	'email' => 'greg@emailaddress.com',
	'website' => 'http://www.gregphoto.net',
	'favoriteNumber' => 'xyz',
	'date' => '11/11/2011'
);

// pass in the data to be validated
$v = new gUtils\InputValidator($data);

// validate each field
$v->field('name')->required()->length(3,50);
$v->field('email', 'Email Address')->required()->email();
$v->field('website')->url();
$v->field('favoriteNumber', 'Your favorite number')->intRange(0,100)->toInt();
$v->field('date')->toDateTime('m/d/Y')->after(time()); // after the current time

if(!$v->allValid()) {
	echo '<pre>';
	print_r($v->getErrors()); // returns an array of errors indexed by field
	echo '<pre>';
	exit();
} 
$data = $v->getValues();  // returns an array of values indexed by field
$name = $v->get('name'); // get the value of name
echo $v->escape('name'); // escape the value of name for output with htmlspecialchars
```

##PasswordHelper 
A utility for creating and verifying bcrypt password hashes and for generating random/temporary passwords

```php
<?php
require('gUtils/PasswordHelper.php');
use gUtils\PasswordHelper;

$pass = new PasswordHelper();
 
// Hash a password with bcrypt and a random salt before storing it in a database
$hash = $pass->generateHash('myP@ssword');
 
// Validate the password against the stored hash on a login attempt
if($pass->compareToHash('myWrongPassword', $hash)) {
  // password matches	
} else {
	// password doesn't match
}
 
// Generate a random password
$randomPassword = $pass->generateRandomPassword();
 
// Validate the complexity of a password
if($pass->checkPasswordComplexity($password)) {
	// password meets requirements	
} else {
	// password doesn't meet requirements
}
```

##RecursiveFileExtensionFilteredIterator
Loops through a directory structure starting at a user-defined path and returns all of the files that match a user-provided list of extensions

```php
<?php
require('gUtils/RecursiveFileExtensionFilteredIterator.php');
use gUtils\RecursiveFileExtensionFilteredIterator;

$path = '/usr/local/apache2/htdocs';
$extensions = array('php','html');
$files = new RecursiveFileExtensionFilteredIterator($path, $extensions);

foreach($files as $file) {
  echo $file->getPathname() . "\n";
}
```

##WeightedRandomSelector:  
A container that holds a number of items and relative weights that allows you to get random items from the list with the chances of getting an item not random, but based on the weight of the item.

```php
<?php
require('gUtils/WeightedRandomSelector.php');
use gUtils\WeightedRandomSelector;

$colors = new WeightedRandomSelector(array(
  array('Blue', 2),
	array('Red', 7),
  array('Purple', 5)
));

echo $colors->get(); // 2/14 change of blue, 7/14 chance of red, 5/14 chance of purple
$manyColors = $colors->getMulti(100); // gets 100 colors in an array
```