<?php
namespace gUtils;

/*
   gutils InputValidator
   Copyright 2011 Greg Neustaetter

   API inspired by ValidFluent (https://github.com/ASoares/PHP-Form-Validation), 
   by Andre Soares (andsoa77@gmail.com)

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

class InputValidator {

    protected $isValid = true;
    protected $allValid = true;
    protected $fields = array();
    protected $currentField;
    protected $trimAll = true;
    public $dynamicValidators = array(
        'alpha' => array(
            'type' => 'regex',
            'pattern' => '/^[[:alpha:]]+$/',
            'errorMsg' => 'alpha'
        ),
        'alnum' => array(
            'type' => 'regex',
            'pattern' => '/^[[:alnum:]]+$/',
            'errorMsg' => 'alnum'
        ),
        'noWhitespace' => array(
            'type' => 'regex',
            'pattern' => '/^\S+$/',
            'errorMsg' => 'noWhitespace'
        )
    );

    public $errorMessages = array(
        'required' => '%s is required',
        'notValid' => '%s is not valid',
        'minLength' => '%s must be at least %d characters',
        'maxLength' => '%s must be fewer than %d characters',
        'intRange' => '%s must be an integer between %d and %d',
        'minValue' => '%s must be at least %d',
        'maxValue' => '%s must be %d or smaller',
        'noMatch' => '%s does not match the value of %s',
        'url' => '%s is not a valid URL',
        'ip' => '%s is not a valid IP address',
        'float' => '%s is not a valid float',
        'boolean' => '%s is not a valid Boolean',
        'alpha' => '%s may only contain the letters A-Z',
        'alnum' => '%s may only contain A-Z and 0-9',
        'noWhitespace' => '%s may not contain spaces, tabs, or other whitespace',
        'date' => '%s is not a valid date',
        'afterField' => '%s is before %s',
        'beforeField' => '%s is after %s',
        'before' => '%s cannot be after %s',
        'after' => '%s cannot be before %s'
    );

    public function __construct($inputArray=array(), $trimAll=true) {
        $this->trimAll = $trimAll;
        foreach ($inputArray as $key => $value) {
            $field = new InputValidatorField($key,$value);
            if($trimAll && is_string($field->getValue())) {
                $field->setValue(trim($value));
            }
            $this->fields[$key] = $field;
        }
    }

/*
* FIELD AND VALUE ACCESSORS
*/

    public function get($name,$filtered=true) {
        return $this->fields[$name]->getValue($filtered);
    }

    public function escape($name,$filtered=true, $flag=ENT_QUOTES) {
        return htmlspecialchars($this->fields[$name]->getValue($filtered),$flag);
    }

    public function getValues($filtered=true) {
        $res = array();
        foreach($this->fields as $name=>$obj) {
            $res[$name] = $obj->getValue($filtered);
        }
        return $res;
    }

    public function val($filtered=true) {
        return $this->getCurrent()->getValue($filtered);
    }

    protected function getCurrent() {
        return $this->currentField;
    }

    // returns internal array of InputValidatorField objects
    public function getFields() {
        return $this->fields;
    }

/*
* VALIDITY AND ERRORS
*/

    // Validity of last field
    public function isValid() {
        return $this->isValid;
	}

    // All fields tested so far must be valid
    public function allValid() {
	   return $this->allValid;
	}

    public function getErrors() {
        $res = array();
        foreach($this->fields as $name=>$obj) {
            if(!$obj->isValid()) {
                $res[$name] = $obj->getError();
            }
        }
        return $res;
    }

/*
* INTERNAL VALIDATION AND FILTERING HELPERS
*/

    protected function setErrorMsg($errorMsg, $default, $params=array()) {
	   $this->allValid = false;
       $this->isValid = false;
       $field = $this->getCurrent();
       if(!empty($errorMsg)) {
           $field->setError($errorMsg);
       } else {
           $vars = array_merge((array)$field->getDisplayName(),(array)$params);
           $field->setError(vsprintf($default, $vars));
       }
	}

    // Validation doesn't need to be performed because the field is already invalid or it is a non-required empty field
    protected function shouldSkipValidation() {
        $val = $this->val();
        return (!$this->isValid() || empty($val));
    }

    protected function regexHelper($regex, $defaultError, $userError='') {
        if(!$this->shouldSkipValidation()) {
            if(!preg_match($regex,$this->val())) {
                $this->setErrorMsg($userError,$this->errorMessages[$defaultError]);
            }
        }
    }

    protected function filterVarHelper($filter, $options, $flags, $defaultError, $userError='') {
        if(!$this->shouldSkipValidation()) {
            $args = array('options' => $options, 'flags' => $flags);
            if(filter_var($this->val(), $filter) === false) {
                $this->setErrorMsg($userError,$this->errorMessages[$defaultError],$options);
            }
        }
    }
    
    protected function convertToDateTime($value, $format=null, $timezone=null) {
        if($value instanceof \DateTime) {
            return $value;
        } elseif(is_int($value)) {
            return new \DateTime('@' . $value);
        } elseif(is_string($value)) {
            if(isset($timezone)) {
                $tz = ($timezone instanceof \DateTimeZone) ? $timezone : new \DateTimeZone($timezone);
            } else {
                $tz = new \DateTimeZone(date_default_timezone_get());
            }
            if(isset($format)) {
                return \DateTime::createFromFormat($format,$value,$tz);
            } else {
                return new \DateTime($value,$tz);   
            }
        } else {
            $type = gettype($value);
            if($type == 'object') {
                $type = get_class($type);
            }
            throw new \InvalidArgumentException("Unable to convert a {$type} to a DateTime object.  Value must be a unix timestamp, a string with a date format, or a DateTime object");
        }
    }

    public function __call($func,$args) {
        if(array_key_exists($func,$this->dynamicValidators)) {
            $options = $this->dynamicValidators[$func];
            switch($options['type']) {
                case 'regex':
                    $userError = (isset($args) && isset($args[0])) ? $args[0] : null;
                    $this->regexHelper($options['pattern'], $options['errorMsg'], $userError);
                    break;
            }
            return $this;
        }
        throw new \BadMethodCallException("Method $func does not exist");
    }



/*
* FIELD METHOD STARTS FILTERING AND VALIDATION FOR A FIELD
*/

    public function field($name, $displayName=null, $initialValue=null) {
	   if(!isset($this->fields[$name])) {
           $this->fields[$name] = new InputValidatorField($name, $initialValue);
       }
       $field = $this->fields[$name];
       if($displayName) {
           $field->setDisplayName($displayName);
       }
       $this->currentField = $field;
       $this->isValid = $this->currentField->isValid();
       return $this;
	}

/*
* FILTERS TO MANIPULATE CURRENT FIELD
*/

    public function trim() {
        if(is_string($this->getCurrent())) {
            $this->getCurrent()->setValue(trim($this->val()));
            return $this;
        }
        throw new \InvalidArgumentException("Value is not a string");
    }

    public function toString() {
        $this->getCurrent()->setValue((string)$this->val());
        return $this;
    }

    public function toInt() {
        $this->getCurrent()->setValue((int)$this->val());
        return $this;
    }

    public function toFloat() {
        $this->getCurrent()->setValue((float)$this->val());
        return $this;
    }

    public function toBoolean() {
        $this->getCurrent()->setValue((bool)$this->val());
        return $this;
    }

    public function filterFunc($func) {
        $this->getCurrent()->setValue(call_user_func($func,$this->val()));
    }

    public function toDateTime($format='m/d/Y', $timezone = null, $errorMsg=null) {
        if(!$this->shouldSkipValidation()) {
            $date = $this->convertToDateTime($this->val(),$format,$timezone);
            if($date === false) {
                $this->setErrorMsg($errorMsg,$this->errorMessages['date']);
            } else {
                $this->getCurrent()->setValue($date);
            }
        }
        return $this;
    }

    public function toUnixTimestamp($format='m/d/Y',$timezone = null, $errorMsg = null) {
        if(is_string($this->val())) {
            $this->toDateTime($format,$timezone,$errorMsg);
        }
        if($this->val() instanceof \DateTime) {
            $this->getCurrent()->setValue($this->val()->getTimestamp());
        }
        return $this;
    }


/*
* VALIDATORS TO VALIDATE CURRENT FIELD
*/

    public function required($default=null, $errorMsg=null) {
        $val = $this->val();
        if(empty($val)) {
            if(isset($default)) {
                $this->getCurrent()->setValue($default);
            } else {
                $this->setErrorMsg($errorMsg,$this->errorMessages['required']);    
            }
        }
    	return $this;
	}

    public function length($min, $max, $errorMsg=null) {
        if(!$this->shouldSkipValidation()) {
            $length = strlen($this->val());
            if($length < (int)$min) {
                $this->setErrorMsg($errorMsg,$this->errorMessages['minLength'],(int)$min);
            } elseif($length > (int)$max) {
                $this->setErrorMsg($errorMsg,$this->errorMessages['maxLength'],(int)$max);
            }
        }
        return $this;
    }

    public function email($errorMsg=null) {
        $this->filterVarHelper(FILTER_VALIDATE_EMAIL, array(), null, 'notValid', $errorMsg);
        return $this;
	}

    public function url($flags=null, $errorMsg=null) {
        $this->filterVarHelper(FILTER_VALIDATE_URL, array(), $flags, 'url', $errorMsg);
        return $this;
    }

    public function ip($flags=null, $errorMsg=null) {
        $this->filterVarHelper(FILTER_VALIDATE_IP, array(), $flags, 'ip', $errorMsg);
        return $this;
    }

    public function float($flags=null, $errorMsg=null) {
        $this->filterVarHelper(FILTER_VALIDATE_FLOAT, array(), $flags, 'float', $errorMsg);
        return $this;
    }

    public function boolean($errorMsg=null) {
       if(!$this->shouldSkipValidation()) {
            if(filter_var($this->val(), FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE) === NULL) {
                $this->setErrorMsg($errorMsg,$this->errorMessages['boolean']);
            }
        }
        return $this;
    }

    public function intRange($min, $max, $errorMsg=null) {
        $this->filterVarHelper(FILTER_VALIDATE_INT, array('min_range' => $min, 'max_range' => $max), null, 'intRange', $errorMsg);
        return $this;
    }

    public function regex($regex, $errorMsg=null) {
        $this->regexHelper($regex, 'notValid', $errorMsg);
        return $this;
    }

    public function matchField($otherField, $errorMsg=null) {
        if($this->val() !== $this->fields[$otherField]->getValue()) {
            $this->setErrorMsg($errorMsg,$this->errorMessages['noMatch'],$this->fields[$otherField]->getDisplayName());
        }
        return $this;
    }

    public function in($array, $errorMsg=null) {
       if(!$this->shouldSkipValidation()) {
            if(!in_array($this->val(), $array)) {
                $this->setErrorMsg($errorMsg,$this->errorMessages['notValid']);
            }
        }
        return $this;
    }

    public function func($func, $errorMsg=null) {
        if(!$this->shouldSkipValidation()) {
            if(call_user_func($func, $this->val()) === false) {
                $this->setErrorMsg($errorMsg,$this->errorMessages['notValid']);
            }
        }
        return $this;
    }

    public function before($value, $format='m/d/Y', $timezone=null, $errorMsg=null) {
        if(!$this->shouldSkipValidation()) {
            $date = $this->convertToDateTime($value,$format,$timezone);
            if($date === false) {
                throw new \InvalidArgumentException("Passed value is not a valid date");
            }
            if($this->val() >= $date) {
                $format = (isset($format)) ? $format : 'm/d/Y';
                $formattedDate = $date->format($format);
                $this->setErrorMsg($errorMsg,$this->errorMessages['before'],$formattedDate);
            }
        }
        return $this;
    }

    public function after($value, $format='m/d/Y', $timezone=null, $errorMsg=null) {
         if(!$this->shouldSkipValidation()) {
            $date = $this->convertToDateTime($value,$format,$timezone);
            if($date === false) {
                throw new \InvalidArgumentException("Passed value is not a valid date");
            }
            if($this->val() <= $date) {
                $format = (isset($format)) ? $format : 'm/d/Y';
                $formattedDate = $date->format($format);
                $this->setErrorMsg($errorMsg,$this->errorMessages['after'],$formattedDate);
            }
        }
        return $this;    
    }

    public function dateRange($start, $end, $format='m/d/Y', $timezone=null, $errorMsg=null) {
        $this->after($start,$format,$timezone,$errorMsg);
        $this->before($end,$format,$timezone,$errorMsg);
        return $this;
    }

    public function afterField($fieldName, $errorMsg=null) {
        if(!$this->shouldSkipValidation()) {
            $otherField = $this->fields[$fieldName];
            if(($this->val() instanceof \DateTime) && ($otherField->getValue() instanceof \DateTime)) {
                if($this->val() <= $this->fields[$fieldName]->getValue()) {
                    $this->setErrorMsg($errorMsg,$this->errorMessages['afterField'],$otherField->getDisplayName());
                }
            } else {
                throw new \InvalidArgumentException("Both fields must be DateTime objects - call toDateTime on them before calling this method");
            }
        }
        return $this;
    }

    public function beforeField($fieldName, $errorMsg=null) {
        if(!$this->shouldSkipValidation()) {
            $otherField = $this->fields[$fieldName];
            if(($this->val() instanceof \DateTime) && ($otherField->getValue() instanceof \DateTime)) {
                if($this->val() >= $this->fields[$fieldName]->getValue()) {
                    $this->setErrorMsg($errorMsg,$this->errorMessages['afterField'],$otherField->getDisplayName());
                }
            } else {
                throw new \InvalidArgumentException("Both fields must be DateTime objects - call toDateTime on them before calling this method");
            }
        }
        return $this;      
    }
}

class InputValidatorField {

    protected $originalValue;
    protected $value;
    protected $error;
    protected $valid;
    protected $name;
    protected $displayName;

    public function __construct($name, $value = null) {
        $this->value = $value;
        $this->originalValue = $value;
        $this->name = $name;
        $this->displayName = ucfirst($name);
        $this->error = null;
        $this->valid = true;
    }

    public function isValid() {
        return $this->valid;
    }

    public function getError() {
        return $this->error;
    }

    public function setError($errorMsg) {
        $this->valid = false;
        $this->error = $errorMsg;
    }

    public function getValue($filtered=true) {
        return ($filtered) ? $this->value : $this->originalValue;
    }

    public function setValue($value, $filtered=true) {
        $this->value = $value;
    }

    public function getDisplayName() {
        return $this->displayName;
    }

    public function setDisplayName($name) {
        $this->displayName = $name;
    }
}