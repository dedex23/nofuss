<?php
namespace Nf\Error;


/**
 * Gestion des exceptions pour le client avec passage d'array
 */
class ClientException extends \Exception{

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

?>
