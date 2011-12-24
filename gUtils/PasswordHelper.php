<?php
namespace gUtils;

/*
   gutils PasswordHelper
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

class PasswordHelper {
	private $badHashes = array('*0', '*1');
	private $base64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';

	public function generateHash($password,$cost=10) {
		if(CRYPT_BLOWFISH != 1) {
			throw new \Exception('This version of PHP does not support Blowfish hashing.  Try PHP version 5.3 or greater');
		}
		$salt = $this->generateBlowfishSalt($cost);
		$hashed = crypt($password, $salt);
		if(in_array($hashed, $this->badHashes)) {
			throw new \Exception('Password hashing failed with value: ' . $hashed);
		} else {
			return $hashed;
		}
	}

	public function compareToHash($password,$hash) {
		return (crypt($password,$hash) == $hash);
	}

	public function generateRandomString($length, $validChars) {
		$string = '';
		$maxIndex = strlen($validChars) - 1;
		for($i=0;$i<$length;$i++) {
			$string .= $validChars[mt_rand(0,$maxIndex)];
		}
		return $string;
	}

	public function generateBlowfishSalt($cost=10) {
		if(($cost < 4) || ($cost > 31)) {
			throw new \Exception('Cost must be an integer in the range 4-31');
		}
		$salt = '$2a$';
		$salt .= ($cost < 10) ? '0' : '';
		$salt .= $cost . '$'; 
		$salt .= $this->generateRandomString(22, $this->base64Chars);
		return $salt;
	}

	public function generateRandomPassword($length=8, $validChars='ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*') {
		return $this->generateRandomString($length, $validChars);
	}

	public function checkPasswordComplexity($password, $minLength=8, $maxLength=50, $patterns=array('/[a-z]/', '/[A-Z]/', '/[0-9]/', '/[!@#$%\^\&\(\)\+=]/'), $minPatternMatches=3) {
		if(count($patterns) < $minPatternMatches) {
			throw Exception('The number of patterns must be greater than or equal to the minimum number of pattern matches');
		}
		if((strlen($password) >= $minLength) && (strlen($password) <= $maxLength)) {
			$patternMatches = 0;
			foreach($patterns as $pattern) {
				if(preg_match($pattern,$password)) {
					$patternMatches++;
				}
			}
			return $patternMatches >= $minPatternMatches;
		}
		return false;
	}
}