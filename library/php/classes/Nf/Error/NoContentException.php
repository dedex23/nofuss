<?php
namespace Nf\Error;

use Nf\Error\HttpException;

class NoContentException extends HttpException {
	protected $_httpStatus = 204;
}
