<?php
namespace Nf\Error\Exception;

class Http extends \Exception{

	protected $_httpStatus = 500;

	public function getHttpStatus(){
		return $this->_httpStatus;
	}
}
