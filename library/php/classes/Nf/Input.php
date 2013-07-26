<?php

namespace Nf;

class Input
{

	const F_INTEGER='int';
	const F_NATURAL='natural';
	const F_NATURALNONZERO='naturalNonZero';
	const F_ALPHA='alpha';
	const F_ALPHANUM='alphaNum';
	const F_NUMERIC='numeric';
	const F_BASE64='base64';
	const F_REGEXP='regexp';
	const F_STRING='string';
	const F_TRIM='trim';
	const F_URL='url';
	const F_STRIPTAGS='stripTags';

	const V_INTEGER='int';
	const V_NATURAL='natural';
	const V_NATURALNONZERO='naturalNonZero';
	const V_ALPHA='alpha';
	const V_ALPHANUM='alphaNum';
	const V_NUMERIC='numeric';
	const V_BASE64='base64';
	const V_EQUALS='equals';
	const V_REGEXP='regexp';
	const V_REQUIRED='required';
	const V_NOTEMPTY='notEmpty';
	const V_GREATERTHAN='greaterThan';
	const V_LESSTHAN='lessThan';
	const V_MINLENGTH='minLength';
	const V_MAXLENGTH='maxLength';
	const V_EXACTLENGTH='exactLength';
	const V_EMAIL='email';
	const V_MATCHES='matches';
	const V_URL='url';
	const V_DEFAULT='default';


	private $_params=array();
	private $_filters=array();
	private $_validators=array();
	private $_fields=array();
	private $_methods=array();

	const REGEXP_ALPHA='/[^a-z]*/i';
	const REGEXP_ALPHANUM='/[^a-z0-9]*/i';
	const REGEXP_BASE64='%[^a-zA-Z0-9/+=]*%i';
	const REGEXP_INT='/^[\-+]?[0-9]+$/';

	public function __construct($params, $filters, $validators) {
		$this->_params=$params;
		$this->_filters=$filters;
		$this->_validators=$validators;
		$this->_classMethods = get_class_methods(__CLASS__);
	}

	public function isValid() {
		// 1) filter
		$this->filter();
		// 2) validate
		return $this->validate();
	}

	public function filter() {
		$this->metaFilterAndValidate('filter');
	}

	public function validate() {
		return $this->metaFilterAndValidate('validate');
	}

	public function getMessages() {
		$messages=array();
		foreach($this->_fields as $fieldName=>$values) {

			if(!$values['isValid']) {
				$invalidators=array();
				foreach($values['validators'] as $validatorName=>$validatorValue) {
					if(!$validatorValue) {
						$invalidators[]=$validatorName;
					}
				}
				$messages[$fieldName]=$invalidators;
				unset($validator);
			}
			unset($fieldName);
			unset($values);
		}
		return $messages;
	}

	public function getFields() {
		return $this->_fields;
	}

	public function getFilteredFields() {
		$filteredFields=array();
		foreach($this->_fields as $fieldName=>$data) {
			$filteredFields[$fieldName]=$data['value'];
		}
		return $filteredFields;
	}

	private function metaFilterAndValidate($metaAction) {

		if($metaAction=='filter') {
			$metaSource=$this->_filters;
		}
		elseif($metaAction=='validate') {
			$metaSource=$this->_validators;
			$isValid=true;
		}

    foreach($metaSource as $paramName=>$options) {

			if($metaAction=='filter') {
				$this->setField($paramName, (isset($this->_params[$paramName]) ? $this->_params[$paramName] : null));
			}
			if($metaAction=='validate') {

				if(!isset($this->_fields[$paramName])) {
					$this->setField($paramName, (isset($this->_params[$paramName]) ? $this->_params[$paramName] : null));
				}

				$validatorIndex=0;
				$validators=array();
			}

			$options=(array)$options;
			foreach($options as $option) {

				if($metaAction=='validate') {
					$validatorIndex++;
				}

				// optional parameter sent to the filter/validator
				// by default, it's not set
				unset($optionParameter);

				if(is_array($option)) {
					$optionKeys=array_keys($option);
					$optionValues=array_values($option);

					// call with an alias and a parameter: array('isValidId' => '\App\Toto::validateId', 22)
					if(isset($option[0]) && $optionKeys[1]==0) {
						$optionName=$optionKeys[0];
						$optionFunction=$optionValues[0];
						$optionParameter=$optionValues[1];
					}
					elseif($this->isAssoc($option)) {
						// call with an alias only : array('isValidId' => '\App\Toto::validateId'),
						$optionKeys=array_keys($option);
						$optionValues=array_values($option);
						$optionName=$optionKeys[0];
						$optionFunction=$optionValues[0];
					}
					else {
						// call with a parameter only : array('regexp', '/[a-z]*/i')
						$optionName=$option[0];
						$optionFunction=$option[0];
						$optionParameter=$option[1];
					}
				}
				else {
					$optionName=$option;
					$optionFunction=$option;
				}

				// echo 'option=' . $optionFunction . '<br>';
				// if we want to validate against a method of a model
				$idx=strpos($optionFunction, '::');
				if($idx!==false) {
					// find (with autoload) the class and call the method
					$className=substr($optionFunction, 0, $idx);
					$methodName=substr($optionFunction, $idx+2);
					if($metaAction=='filter') {
						if(isset($optionParameter)) {
							$this->setField($paramName, $className::$methodName($this->_fields[$paramName]['value'], $optionParameter));
						}
						else {
							$this->setField($paramName, $className::$methodName($this->_fields[$paramName]['value']));
						}
					}
					elseif($metaAction=='validate') {
						if(isset($optionParameter)) {
							$ret=$className::$methodName($this->_fields[$paramName]['value'], $optionParameter, $this);
						}
						else {
							$ret=$className::$methodName($this->_fields[$paramName]['value'], null, $this);
            }
						// add the validator to the validators for this field
            $isValid=$isValid && $ret;
						$validators[$optionName]=$ret;
					}
				}
				else {
					// we will search for the function name in this class
					$methodNameForOption=$metaAction . ucfirst($optionFunction);
					if(in_array($methodNameForOption, $this->_classMethods)) {
						if(isset($optionParameter)) {
							$ret=self::$methodNameForOption($this->_fields[$paramName]['value'], $optionParameter, $this);
						}
						else {
							$ret=self::$methodNameForOption($this->_fields[$paramName]['value'], null, $this);
						}
						if($metaAction=='filter') {
							$this->setField($paramName, $ret);
						}
						// add the validator to the validators for this field
						if($metaAction=='validate') {
							// special case of the default value
							if($optionName==self::V_DEFAULT) {
								$this->_fields[$paramName]['value']=$ret;
								$ret=true;
							}
							$isValid=$isValid && $ret;
							$validators[$optionName]=$ret;
						}
					}
					else {
						throw new \Exception (__CLASS__ . ' hasn\'t a method called "' . $methodNameForOption . '"');
					}
        }
			}
			unset($option);

			// we set the field after all the input value went through all validators
      if($metaAction=='validate' && $validatorIndex==count($options)) {

        // we test for each params if one of validators is not valid.
        $paramIsValid = true;
        foreach($validators as $v){
          if ( $v === false){
            $paramIsValid = false;
            break;
          }
        }
				$this->setField($paramName, false, $paramIsValid, $validators);
			}
		}
		if($metaAction=='validate') {
			return $isValid;
		}
	}

	// assign a field's value, isValid and validators' list.
	private function setField($paramName, $value=false, $isValid=null, $validators=null) {
		if(!isset($this->_fields[$paramName])) {
			$this->_fields[$paramName]=array(
						'originalValue' => (isset($this->_params[$paramName]))?$this->_params[$paramName]:null,
						'value' => (isset($this->_params[$paramName]))?$this->_params[$paramName]:null,
						'isValid' => true,
						'validators' => array()
						);
		}
		if($value!==false) {
			$this->_fields[$paramName]['value']=$value;
		}
		if($isValid!==null) {
			$this->_fields[$paramName]['isValid']=$this->_fields[$paramName]['isValid'] && $isValid;
		}
		if($validators!==null) {
			$this->_fields[$paramName]['validators']=$validators;
		}
	}

	// returns the filtered value for any field given in the params
	public function __get($paramName) {
		return $this->_fields[$paramName]['value'];
	}

	public function __isset($paramName) {
		return isset($this->_fields[$paramName]['value']);
	}

	private function isAssoc($array) {
		return is_array($array) && array_diff_key($array, array_keys(array_keys($array)));
	}

	// ************************************************************************
	// filter functions
  // ************************************************************************

 // used for int as string 
  public static function filterNull($value){
    if ( $value == '') return null;
    return $value;
  }

	public static function filterInt($value) {
    return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    if ( $d == '')
      return null;
    return (int)$d;
	}

	public static function filterNatural($value) {
		return abs(self::filterInt($value));
	}

  public static function filterNaturalNonZero($value) {
		$natural=self::filterNatural($value);
		if($natural!=0) {
			return $natural;
		}
		else {
			return null;
		}
	}

	public static function filterAlpha($value) {
		return preg_replace(self::REGEXP_ALPHA, '', $value);
	}

	public static function filterAlphaNum($value) {
		return preg_replace(self::REGEXP_ALPHANUM, '', $value);
	}

	public static function filterBase64($value) {
		return preg_replace(self::REGEXP_BASE64, '', $value);
	}

	public static function filterNumeric($value) {
		return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}

	public static function filterStripTags($value) {
		return strip_tags($value);
	}

	public static function filterRegexp($value, $regexp) {
		return preg_replace($regexp, '', $value);
	}

	public static function filterString($value) {
		return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
	}

	public static function filterTrim($value) {
		return trim($value);
	}

	public static function filterUrl($value) {
		return filter_var($value, FILTER_SANITIZE_URL);
	}

	// ************************************************************************
	// validator functions
	// ************************************************************************

	public static function validateInt($value){
		return (bool)preg_match(self::REGEXP_INT, $value);
	}

	public static function validateNatural($value) {
		return ($this->filterNatural($value)==$value);
	}

	public static function validateNaturalNonZero($value) {
		return ($this->filterNaturalNonZero($value)==$value);
	}

	public static function validateAlpha($value) {
		return (bool)preg_match(self::REGEXP_ALPHA, $value);
	}

	public static function validateAlphaNum($value) {
		return (bool)preg_match(self::REGEXP_ALPHANUM, $value);
	}

	public static function validateBase64($value) {
		return (bool)preg_match(self::REGEXP_BASE64, $value);
	}

	public static function validateNumeric($value) {
		return (self::filterNumeric($value)==$value);
	}

	public static function validateEquals($value, $check) {
		return (bool)($value==$check);
	}

	public static function validateRegexp($value, $regexp) {
		return (bool)preg_match($regexp, $value);
	}

	public static function validateRequired($value) {
		return ($value!==null);
	}

	public static function validateNotEmpty($value) {
		return !(trim($value) === '');
	}

	public static function validateGreaterThan($value, $compare) {
		return ($value>=$compare);
	}

	public static function validateLessThan($value, $compare) {
		return ($value<=$compare);
	}

	public static function validateMinLength($value, $compare) {
		return (mb_strlen($value)>=$compare);
	}

	public static function validateMaxLength($value, $compare) {
		return (mb_strlen($value)<=$compare);
	}

	public static function validateExactLength($value, $compare) {
		return (mb_strlen($value)==$compare);
	}

	public static function validateEmail($value) {
		$regexp='/^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i';
		return (bool)preg_match($regexp, $value);
	}

	public static function validateMatches($value, $compareField, $instance) {
		if(isset($instance->_fields[$compareField])) {
			return ($value==$instance->_fields[$compareField]['value']);
		}
	}

	public static function validateUrl($value) {
		if(($url = parse_url($value)) && !empty($url['scheme']) && !empty($url['host'])) {
			return true;
		}
		return false;
	}

	public static function validateDefault($value, $defaultValue) {
		if(empty($value)) {
			return $defaultValue;
		}
		return $value;
	}

}
