<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class Forbidden extends Http {
	protected $_httpStatus = 403;
}
