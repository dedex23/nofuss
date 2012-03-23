<?php

namespace Nf;

abstract class Session extends Singleton
{
	protected static $_instance=null;

	protected static $_data=false;

	public static function factory($type, $params, $lifetime) {
		$className=get_class() . '\\' . ucfirst($type);
		return new $className($params, $lifetime);
	}

	public static function start() {
		$config=Registry::get('config');
		$sessionHandlerName=$config->session->handler;
		if(isset($config->session->params)) {
			$sessionParams=$config->session->params;
		}
		$sessionHandler=self::factory($sessionHandlerName, $sessionParams, $config->session->lifetime);
		session_set_cookie_params(0, $config->session->cookie->path, $config->session->cookie->domain, false, true);
	    session_name($config->session->cookie->name);
	    session_set_save_handler(
			array(&$sessionHandler, 'open'),
			array(&$sessionHandler, 'close'),
			array(&$sessionHandler, 'read'),
			array(&$sessionHandler, 'write'),
			array(&$sessionHandler, 'destroy'),
			array(&$sessionHandler, 'gc')
		);
	    session_start();
		session_regenerate_id(true);
	    Registry::set('session', $sessionHandler);
	    return $sessionHandler;
	}

	public static function getData() {
		return self::$_data;
	}

	public static function get($key) {
		if(isset(self::$_data[$key])) {
			return self::$_data[$key];
		}
		else {
			return false;
		}
	}

	public static function set($key, $value) {
		self::$_data[$key]=$value;
	}


}