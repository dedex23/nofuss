<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class Unauthorized extends Http {
	protected $_httpStatus = 401;
}
