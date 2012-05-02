<?php

namespace Nf\Error;

class Handler extends \Exception
{

	static $lastError=array(
							'type' => 'error',
							'httpCode' => 0,
							'string' => '',
							'number' => 0,
							'file' => '',
							'line' => 0,
							'trace'	=> ''
							);

	public static function getLastError() {
		return self::$lastError;
	}

	public static function disableErrorHandler() {
		while (set_error_handler(create_function('$errno,$errstr', 'return false;'))) {
            // Unset the error handler we just set.
            restore_error_handler();
            // Unset the previous error handler.
            restore_error_handler();
        }
        // Restore the built-in error handler.
        restore_error_handler();

		while (set_exception_handler(create_function('$e', 'return false;'))) {
            // Unset the error handler we just set.
            restore_exception_handler();
            // Unset the previous error handler.
            restore_exception_handler();
        }
        // Restore the built-in error handler.
        restore_exception_handler();
	}

	public static function handleError($errno = null, $errstr = 0, $errfile = null, $errline = null) {
		$error_reporting=error_reporting();
		if($error_reporting==0) {
			return true; // developer used @ to ignore all errors

		}
		if(!($error_reporting & $errno)) {
			return true; // developer asked to ignore this error
		}
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		return false;
	}

	public static function handleException($exception) {
		self::disableErrorHandler();
		self::$lastError['type']='exception';
		self::$lastError['httpCode']=500;
		self::$lastError['string']=$exception->getMessage();
		self::$lastError['number']=0;
		self::$lastError['file']=$exception->getFile();
		self::$lastError['line']=$exception->getLine();
		self::$lastError['trace']=$exception->getTraceAsString();
		$str='Exception : ' . $exception->getMessage() . ' in file : ' . $exception->getFile() . ' (line ' . $exception->getLine() . ')';
		return self::displayAndLogError($str, 500);
	}

	public static function handleFatal() {
		self::disableErrorHandler();
		$last=error_get_last();
		if($last!=null) {
			self::$lastError['type']='fatal';
			self::$lastError['httpCode']=500;
			self::$lastError['message']=$last['message'];
			self::$lastError['number']=$last['type'];
			self::$lastError['file']=$last['file'];
			self::$lastError['line']=$last['line'];
			self::$lastError['trace']=$last['trace'];
			return self::displayAndLogError(print_r($last, true), 500);
		}
	}

	public static function handleForbidden($httpCode=403, $friendlyMessage='') {
		return self::handleHttpError('forbidden', $httpCode, $friendlyMessage);
	}

	public static function handleNotFound($httpCode=404, $friendlyMessage='') {
		return self::handleHttpError('notFound', $httpCode, $friendlyMessage);
	}

	private static function handleHttpError($type='notFound', $httpCode, $friendlyMessage='') {
		self::$lastError['type']=$type;
		self::$lastError['httpCode']=$httpCode;
		self::$lastError['string']=$friendlyMessage;
		self::$lastError['number']=0;
		self::$lastError['file']='';
		self::$lastError['line']=0;
		self::$lastError['trace']='';

		if(\Nf\Registry::isRegistered('config')) {
			$config=\Nf\Registry::get('config');
			$front=\Nf\Front::getInstance();
			$response=$front->getResponse();
			$response->clearBody();
			$response->clearBuffer();
			try {
				$response->setHttpResponseCode($httpCode);
				$response->sendHeaders();
			}
			catch(Exception $e) { }

			$configName=strtolower($type);

			if(isset($config->error->displayMethod)) {
				if($config->error->displayMethod=='forward') {
					// forward
					if(!$front->forward(
									$config->$configName->forward->module,
									$config->$configName->forward->controller,
									$config->$configName->forward->action
									)) {
						ini_set('display_errors', 'On');
						trigger_error('Error Handler failed to forward to the error controller.', E_USER_ERROR);
					}
					return;
				}
			}
		}
	}

	public static function displayAndLogError($str, $httpCode) {
		if(\Nf\Registry::isRegistered('config')) {
			$config=\Nf\Registry::get('config');
			$front=\Nf\Front::getInstance();
			$response=$front->getResponse();
			$response->clearBody();
			$response->clearBuffer();
			try {
				$response->setHttpResponseCode($httpCode);
				$response->sendHeaders();
			}
			catch(Exception $e) { }

			if(isset($config->error->displayMethod)) {
				if($config->error->displayMethod=='forward') {
					// forward
					if(!$front->forward(
									$config->error->forward->module,
									$config->error->forward->controller,
									$config->error->forward->action
									)) {
						trigger_error($str);
					}
					return true;
				}
			}
			// default : display
			$response->displayError($str);
			return true;
		}
		else {
			@header('HTTP/1.1 500 Internal Server Error');
			print_r($str);
			error_log($str);
			return true;
		}
	}

	public static function setErrorHandler() {
		set_error_handler(array('Nf\Error\Handler', 'handleError'));
		set_exception_handler(array('Nf\Error\Handler', 'handleException'));
		register_shutdown_function(array('Nf\Error\Handler', 'handleFatal'));
	}

	public static function setErrorDisplaying() {
		if(\Nf\Registry::isRegistered('config')) {

			$config=\Nf\Registry::get('config');
			if(isset($config->error->displayPHPErrors) && (strtolower($config->error->displayPHPErrors)=='off' || $config->error->displayPHPErrors==0)) {
				ini_set('display_errors', 'On'); // don't display the errors
			}
			else {
				ini_set('display_errors', 'On'); // don't display the errors
			}
		}
		else {
			ini_set('display_errors', 'On');
		}
	}

}