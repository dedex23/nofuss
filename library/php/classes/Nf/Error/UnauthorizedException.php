<?php
namespace Nf\Error;

use Nf\Error\HttpException;

class UnauthorizedException extends HttpException {
	protected $_httpStatus = 401;
}
?>
