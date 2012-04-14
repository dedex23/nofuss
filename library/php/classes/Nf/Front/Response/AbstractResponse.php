<?php

namespace Nf\Front\Response;

abstract class AbstractResponse
{

	protected $_bodyParts=array();

	public function addBodyPart($bodyPart) {
		$this->_bodyParts[]=$bodyPart;
	}

    public function clearBody() {
        $this->_bodyParts=array();
    }

    public function clearBuffer() {
    	$maxObLevel=\Nf\Front::$obLevel;
    	$curObLevel = ob_get_level();
        if ($curObLevel > $maxObLevel) {
            do {
                ob_end_clean();
                $curObLevel = ob_get_level();
            } while ($curObLevel > $maxObLevel);
        }
	}

    public function output() {
        echo implode('', $this->_bodyParts);
    }

    public function sendResponse() {
        $this->sendHeaders();
        $this->output();
    }

    public function displayError($str) {
    	// $this->sendHeaders();
		echo '<pre>';
    	echo 'erreur !';
		debug_print_backtrace();
		echo '</pre>';
		// echo $str;
	}

	public function cr() {
		return "\n";
	}

}