<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class NoContent extends Http {
	protected $_httpStatus = 204;
}
