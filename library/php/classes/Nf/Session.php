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
		session_name($config->session->cookie->name);
		session_set_cookie_params(0, $config->session->cookie->path, $config->session->cookie->domain, false, true);
	    session_set_save_handler(
			array(&$sessionHandler, 'open'),
			array(&$sessionHandler, 'close'),
			array(&$sessionHandler, 'read'),
			array(&$sessionHandler, 'write'),
			array(&$sessionHandler, 'destroy'),
			array(&$sessionHandler, 'gc')
		);
		register_shutdown_function('session_write_close');
	    session_start();
		//session_regenerate_id(true);
	    Registry::set('session', $sessionHandler);
	    return $sessionHandler;
	}

	public static function getData() {
		return self::$_data;
	}

	public function __get($key) {
		return self::get($key);
	}

	public function __set($key, $value) {
		return self::set($key, $value);
	}

	public static function get($key) {
		if(isset(self::$_data[$key])) {
			return self::$_data[$key];
		}
		else {
			return null;
		}
	}

	public static function set($key, $value) {
		self::$_data[$key]=$value;
	}

	public static function delete($key) {
		unset(self::$_data[$key]);
	}


}