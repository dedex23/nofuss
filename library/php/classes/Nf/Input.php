<?php

namespace Nf;

class Input
{

	const F_INTEGER='Int';
	const F_NATURAL='Natural';
	const F_NATURALNONZERO='NaturalNonZero';
	const F_ALPHA='Alpha';
	const F_ALPHANUM='AlphaNum';
	const F_NUMERIC='Numeric';
	const F_BASE64='Base64';
	const F_REGEXP='Regexp';
	const F_STRING='String';
	const F_TRIM='Trim';
	const F_URL='Url';
	const F_STRIPTAGS='StripTags';
	const F_NULL='NullIfEmptyString';
	const F_BOOLEAN='Boolean';

	const V_INTEGER='Int';
	const V_NATURAL='Natural';
	const V_NATURALNONZERO='NaturalNonZero';
	const V_ALPHA='Alpha';
	const V_ALPHANUM='AlphaNum';
	const V_NUMERIC='Numeric';
	const V_BASE64='Base64';
	const V_EQUALS='Equals';
	const V_REGEXP='Regexp';
	const V_REQUIRED='Required';
	const V_NOTEMPTY='NotEmpty';
	const V_GREATERTHAN='GreaterThan';
	const V_LESSTHAN='LessThan';
	const V_MINLENGTH='MinLength';
	const V_MAXLENGTH='MaxLength';
	const V_EXACTLENGTH='ExactLength';
	const V_EMAIL='Email';
	const V_MATCHES='Matches';
	const V_URL='Url';
	const V_DEFAULT='Default';
	const V_BOOLEAN='Boolean';

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
		$refl = new \ReflectionClass(__CLASS__);
		$this->_classConstants = $refl->getConstants();
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
						// or (if your name is Olivier D) call with the parameter as assoc : array('default' => 7),
						$optionKeys=array_keys($option);
						$optionValues=array_values($option);

						// if the value of the array is a function
						if(isset($$optionFunction)) {
							$optionName=$optionKeys[0];
							$optionFunction=$optionValues[0];
						}
						// if the value of the array is a function (à la Olivier D)
						else {
							$optionName=$optionKeys[0];
							$optionFunction=$optionKeys[0];
							$optionParameter=$optionValues[0];
						}
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
					// if the developer has used a shortname for the filter/validator
					$methodNameFromConstants = (($metaAction=='filter')?'F':'V') . '_' . strtoupper($optionFunction);
					if(isset($this->_classConstants[$methodNameFromConstants])) {
						$methodNameForOption = (($metaAction=='filter')?'filter':'validate') . $this->_classConstants[$methodNameFromConstants];
					}

					if(in_array($methodNameForOption, $this->_classMethods)) {
						if($methodNameForOption=='validateRequired') {
							$ret=isset($this->_fields[$paramName]);
						}
						else {
							if(!isset($optionParameter)) {
								$optionParameter=null;
							}
							if(is_array($this->_fields[$paramName]['value'])) {
								if($metaAction=='filter') {
									foreach($this->_fields[$paramName]['value'] as $paramKey => $paramValue) {
										$this->_fields[$paramName]['value'][$paramKey]=self::$methodNameForOption($this->_fields[$paramName]['value'][$paramKey], $optionParameter, $this);
									}
									unset($paramKey);
									unset($paramValue);
									$ret=$this->_fields[$paramName]['value'];
								}
								else {
									$ret=true;
									foreach($this->_fields[$paramName]['value'] as $paramKey => $paramValue) {
										$ret&=self::$methodNameForOption($this->_fields[$paramName]['value'][$paramKey], $optionParameter, $this);
									}
									unset($paramKey);
									unset($paramValue);
								}
							}
							else {
								$ret=self::$methodNameForOption($this->_fields[$paramName]['value'], $optionParameter, $this);
							}
						}
						if($metaAction=='filter') {
							$this->setField($paramName, $ret);
						}
						// add the validator to the validators for this field
						if($metaAction=='validate') {
							// special case of the default value
							if($methodNameForOption=='validateDefault') {
								if(is_array($this->_fields[$paramName]['value'])) {
									foreach($this->_fields[$paramName]['value'] as $paramKey => $paramValue) {
										if(empty($this->_fields[$paramName]['value'][$paramKey])) {
											$this->_fields[$paramName]['value'][$paramKey]=$optionParameter;
										}
									}
									unset($paramKey);
									unset($paramValue);
									$ret=true;
								}
								else {
									if(empty($this->_fields[$paramName]['value'])) {
										$this->_fields[$paramName]['value']=$optionParameter;
									}
									$ret=true;
								}
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

 	// used for int as string in json data
	public static function filterNullIfEmptyString($value){
		if ($value == '') return null;
    	return $value;
  	}

	public static function filterInt($value) {
	    return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
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

	public static function filterBoolean($value) {
		$out = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		return $out;
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
		return (self::filterInt($value)==$value);
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

	public static function validateBoolean($value) {
		return (self::filterBoolean($value)==$value);
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
