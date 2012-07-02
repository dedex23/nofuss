<?php

namespace Nf\Front\Response;

class Cli extends AbstractResponse
{

    public function setHeader($name, $value, $replace = false) {
        return true;
    }

    public function redirect($url, $code = 302) {
		throw new Exception ('cannot redirect in cli version');
    }

    public function clearHeaders() {
        return false;
    }


    public function setHttpResponseCode($code) {
        return true;
    }

    public function canSendHeaders() {
        return true;
    }

	public function sendHeaders() {
        return false;
    }

}
