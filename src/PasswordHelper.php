<?php
/*
PasswordHelper version 1.0
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

class PasswordHelper {
	private $badHashes = array('*0', '*1');
	private $base64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';

	public function generateHash($password,$cost=10) {
		if(CRYPT_BLOWFISH != 1) {
			throw new Exception('This version of PHP does not support Blowfish hashing.  Try PHP version 5.3 or greater');
		}
		$salt = $this->generateBlowfishSalt($cost);
		$hashed = crypt($password, $salt);
		if(in_array($hashed, $this->badHashes)) {
			throw new Exception('Password hashing failed with value: ' . $hashed);
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
			throw new Exception('Cost must be an integer in the range 4-31');
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