<?php
namespace Nf\Error;

use Nf\Error\HttpException;

class NotFoundException extends HttpException {
	protected $_httpStatus = 404;
}
?>
