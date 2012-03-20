<?php

namespace App;

class Toto {

	public $a;

	public function __construct($a) {
		$this->a=$a;
		$front=\Nf\Front::getInstance();
		$view=$front->getView();
		$view->str='from toto';
	}

	public static function validateId($id, $param=null) {
		// echo '<br>validateId dans Toto=' . $id . ' avec param = ' . $param . '<br>';
		return false;
	}

}
