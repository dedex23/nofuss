<?php

namespace Nf\Front;

class Controller {

	protected $_front;
	protected $_view;

	public function __construct($front) {
		$this->_front=$front;
	}

	public function getParams() {
		return $this->_front->getParams();
	}

	public function __get($var) {
		if($var=='view') {
			if(is_null($this->_view)) {
				$this->_view=$this->_front->getView();
			}
			return $this->_view;
		}
		elseif($var=='session') {
			return $this->_front->getSession();
		}
		elseif($var=='request') {
			return $this->_front->getRequest();
		}
		elseif($var=='response') {
			return $this->_front->getResponse();
		}
		else {
			return $this->$var;
		}
	}

	// called after dispatch
	public function postDispatch() {

	}

	// called after action
	public function postAction() {

	}

	public function getLabel($lbl) {
		// return \Nf\LabelManager::get($lbl);
	}

}
