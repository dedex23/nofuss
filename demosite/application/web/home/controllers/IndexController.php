<?php

namespace App\Home;

class IndexController extends \App\Controller {

	function indexAction(){
		$this->view->str='world';
		$this->view->render('index');
	}

}
