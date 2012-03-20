<?php

namespace Nf;

abstract class Session extends Singleton
{
	protected static $_instance=null;

	protected $_data;

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

	public function getData() {
		return $this->_data;
	}

	public function __get($key) {
		if(isset($this->_data[$key])) {
			return $this->_data[$key];
		}
		else {
			return false;
		}
	}

	public function __set($key, $value) {
		$this->_data[$key]=$value;
	}


}