<?php
namespace Nf\Error;

class HttpException extends \Exception{

	protected $_httpStatus = 200;

	public function getHttpStatus(){
		return $this->_httpStatus;
	}
}
