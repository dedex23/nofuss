<?php

namespace App;

class Controller extends \Nf\Front\Controller {

	public function __construct($front) {
		parent::__construct($front);
		// ici par exemple des tests sur le moduleName pour effectuer une demande de login, etc
	}

}
