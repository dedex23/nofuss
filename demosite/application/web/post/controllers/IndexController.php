<?php

namespace App;

class Post_IndexController extends \App\Controller {

	function viewAction(){

		$params=$this->request->getParams();
		print_r($params);

	}

}
