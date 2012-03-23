<?php

namespace App\Home;

class IndexController extends \App\Controller {

	function indexAction(){
		echo 'index';
	}

	function testAction(){

		$front->getCache('global');

		$front=\Nf\Front::getInstance();
		$cache=$front->getCache('global');


		if($cache->load('test_id', 1)!==false) {
			echo 'en cache : ' . $cache->load('test_id', 1) . '<br>';
		}
		else {
			var_dump($cache->save('test_id', 1, 'coucou'), 30);
		}


		$dbSeo=\Nf\Db::getConnection('data', '127.0.0.1', 'nofuss', false);
		$sql="SELECT 1";
		$res=$dbSeo->query($sql);

		return;

		$params=$this->request->getParams();

		if($params['id']==1) {
			\Nf\Error\Handler::handleNotFound(404, 'nope, id not found');
		}
		else {

			$this->view->str='hi';
			// $this->response->setCachable(10);

			$params['id']="\t   mlalmkéé\n";
			$params['id']="456";
			$params['id2']='a54654aa';

			$filters=array(
				'id' => array('string', 'trim')
			);

			$validators=array(
				'id' => array(
							//'int',
							// you can send an array to set the validator name in the return values
							// array('isValidId' => '\App\Toto::validateId', 22),
							// array('isValidIdParam' => '\App\Toto::validateId'),
							array('regexp', '/^[a-z0-9]+$/'),
							'required',
				),
				'id2' => array(
							array('matches', 'id'),
				),
				'name' => array(
							'required',
							'notEmpty',
				),
			);

			$input=new \Nf\Input($params, $filters, $validators);

			// echo 'num=' . $input->filterNumeric('56454.5');

			if($input->isValid()) {
				echo 'da';

			}
			else {
				echo 'niet';
				print_r($input->getFields());
			}


			/*
			echo 'id=' . $params['id'] . '<br />';

			echo 'ob_level=' . ob_get_level() . '<br />';

			if(isset($params['a'])) {
				echo 'a=' . $params['a'] . '<br />';
			}



			ob_start();

			trigger_error('coucou');

			$toto=new Toto(25);

			// echo 'pute';
			$front=\Nf\Front::getInstance();
			$cache=$front->getCache('global');

			if($cache->load('test_id', $params['id'])!==false) {
				echo 'en cache : ' . $cache->load('test_id', $params['id']) . '<br>';
			}
			else {
				var_dump($cache->save('test_id', $params['id'], 'coucou'), 30);
			}



			print_r($this->session->getData());

			$this->session->toto=array('toto');

			if(isset($params['a']) && $params['a']==100) {
				$this->response->redirect('/test_3?a=1', 302);
			}

			$config=\Nf\Registry::get('config');
			$conf=$config->db->site;
			$db_site=\Nf\Db::factory($conf);

			$dbSeo=\Nf\Db::getConnection('data', array('hostname' => '...')

			$sql="SELECT * from latable";
			$res=$db_site->query($sql);
			if($res->rowCount()>0) {
				$rows=$res->fetchAll();
				print_r($rows);
			}

			// strpo();
			*/

			echo $this->view->fetch('index');
		}
	}

	function helloAction(){
		echo 'hello world! <em>direct</em>';
	}

	function listAction(){
		for($i=1; $i<10; $i++) {
			echo $i . '<br>';
		}
	}

}
