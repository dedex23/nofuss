<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class NotFound extends Http {
	protected $_httpStatus = 404;
}
