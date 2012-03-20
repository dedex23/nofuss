<?php

namespace Nf\Front\Request;

class Http extends AbstractRequest
{

	protected $_params=array();

	public function __construct() {
		if(!empty($_SERVER['REDIRECT_URL'])) {
			$uri=ltrim($_SERVER['REDIRECT_URL'], '/');
		}
		else {
			$uri=ltrim($_SERVER['REQUEST_URI'], '/');
		}
		$this->_uri=$uri;
	}

	public function sanitizeUri() {
		// filter the uri according to the config of security.restrictCharactersInUrl
		// this option only allows us to use Alpha-numeric text, Tilde: ~, Period: ., Colon: :, Underscore: _, Dash: -
		$config=\Nf\Registry::get('config');
		if(isset($config->security->restrictCharactersInUrl) && $config->security->restrictCharactersInUrl) {
			if (preg_match('%[\w0-9~.,/@\-=[\]{}|&?!]*%i', $this->_uri, $regs)) {
				if($this->_uri==$regs[0]) {
					return true;
				}
			}
			return false;
		}
		else {
			return true;
		}
	}

	public function getUri() {
		return $this->_uri;
	}

	public function getParams() {
        $return       = $this->_params;
        $paramSources = $this->getParamSources();
        if (in_array('_GET', $paramSources)
            && isset($_GET)
            && is_array($_GET)
        ) {
            $return += $_GET;
        }
        if (in_array('_POST', $paramSources)
            && isset($_POST)
            && is_array($_POST)
        ) {
            $return += $_POST;
        }
        return $return;
    }

    // handle the redirection according to the trailing slash configuration
	public function redirectForTrailingSlash(){
		$config=\Nf\Registry::get('config');
		$redirectionUrl=false;
		$requestParams='';
		$requestPage='/' . $this->_uri;
		// we don't redirect for the home page...
		if($requestPage!='/') {
			// the url without the params is :
			if(mb_strpos($requestPage, '?')!==false) {
				$requestParams=mb_substr($requestPage, mb_strpos($requestPage, '?'), mb_strlen($requestPage) - mb_strpos($requestPage, '?'));
				$requestPage=mb_substr($requestPage, 0, mb_strpos($requestPage, '?'));
			}

			if($config->trailingSlash->needed==true) {
				if(mb_substr($requestPage, -1, 1)!='/') {
					$redirectionUrl='http://' . $_SERVER['HTTP_HOST'] . $requestPage . '/' . $requestParams;
				}
			}
			else {
				if(mb_substr($requestPage, -1, 1)=='/') {
					$redirectionUrl='http://' . $_SERVER['HTTP_HOST'] . rtrim($requestPage, '/') . $requestParams;
				}
			}

			if($redirectionUrl!==false) {
				$response = new \Nf\Front\Response\Http();
				$response->redirect($redirectionUrl, 301);
				$response->sendHeaders();
				return true;
			}
		}

		return false;
	}

}