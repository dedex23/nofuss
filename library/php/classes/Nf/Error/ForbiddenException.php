<?php
namespace Nf\Error;

use Nf\Error\HttpException;

class ForbiddenException extends HttpException {
	protected $_httpStatus = 403;
}
