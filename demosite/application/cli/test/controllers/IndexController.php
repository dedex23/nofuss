<?php

namespace App\Test;

class IndexController extends \App\Controller {

	function indexAction(){
		for($i=0; $i<500; $i++) {
			usleep(500);
			echo 'index';
		}
	}

}
