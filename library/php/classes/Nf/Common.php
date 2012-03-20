<?php

namespace Nf;

class Common extends Singleton
{

	public static function mkdir_recursive($pathname, $mode)
	{
    	is_dir(dirname($pathname)) || self::mkdir_recursive(dirname($pathname), $mode);
    	return is_dir($pathname) || @mkdir($pathname, $mode);
	}

}