<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

/**
 * Gestion des exceptions pour le client avec passage d'array
 */
class BadRequest extends Http{

	protected $_httpStatus = 400;

	private $_errors = null;
	/**
	 * @param array $errors
	 */
	public function __construct($errors){

		if ( is_string($errors))
			$errors = array($errors);

		$this->_errors = $errors;
		parent::__construct();
	}

  public function getErrors(){
    return $this->_errors;
  }
}
