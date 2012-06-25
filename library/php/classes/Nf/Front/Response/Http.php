<?php

// inspired from Zend Framework

namespace Nf\Front\Response;

class Http extends AbstractResponse
{

    protected $_headers = array();

    protected $_headersRaw = array();

    protected $_httpResponseCode = 200;

    protected $_isRedirect = false;

    protected function _normalizeHeader($name) {
        $filtered = str_replace(array('-', '_'), ' ', (string) $name);
        $filtered = ucwords(strtolower($filtered));
        $filtered = str_replace(' ', '-', $filtered);
        return $filtered;
    }

    public function setHeader($name, $value, $replace = false) {
        $this->canSendHeaders(true);
        $name  = $this->_normalizeHeader($name);
        $value = (string) $value;

        if ($replace) {
            foreach ($this->_headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->_headers[$key]);
                }
            }
        }
        $this->_headers[] = array(
            'name'    => $name,
            'value'   => $value,
            'replace' => $replace
        );
        return $this;
    }

    public function redirect($url, $code = 302, $exit=true) {
        $this->canSendHeaders();
        $this->setHeader('Location', $url, true)
             ->setHttpResponseCode($code);
    	if($exit) {
    		$front=\Nf\Front::getInstance();
    		$front->postLaunchAction();
    		$this->clearBuffer();
    		$this->clearBody();
    		$this->sendHeaders();
    		exit;
    	}
		return $this;
    }

    public function isRedirect() {
        return $this->_isRedirect;
    }

    public function getHeaders() {
        return $this->_headers;
    }

    public function clearHeaders() {
        $this->_headers = array();

        return $this;
    }

    public function clearHeader($name) {
        if (! count($this->_headers)) {
            return $this;
        }
        foreach ($this->_headers as $index => $header) {
            if ($name == $header['name']) {
                unset($this->_headers[$index]);
            }
        }
        return $this;
    }

    public function setRawHeader($value) {
        $this->canSendHeaders();
        if ('Location' == substr($value, 0, 8)) {
            $this->_isRedirect = true;
        }
        $this->_headersRaw[] = (string) $value;
        return $this;
    }

    public function clearRawHeaders() {
        $this->_headersRaw = array();
        return $this;
    }

    public function clearRawHeader($headerRaw) {
        if (! count($this->_headersRaw)) {
            return $this;
        }
        $key = array_search($headerRaw, $this->_headersRaw);
        unset($this->_headersRaw[$key]);
        return $this;
    }

    public function clearAllHeaders() {
        return $this->clearHeaders()
                    ->clearRawHeaders();
    }

    public function setHttpResponseCode($code) {
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            throw new \Exception('Invalid HTTP response code');
        }
        if ((300 <= $code) && (307 >= $code)) {
            $this->_isRedirect = true;
        } else {
            $this->_isRedirect = false;
        }
        $this->_httpResponseCode = $code;
        return $this;
    }

    public function canSendHeaders() {
        $ok = headers_sent($file, $line);
        if ($ok) {
            trigger_error('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }
        return !$ok;
    }

	public function sendHeaders() {
        // Only check if we can send headers if we have headers to send
        if (count($this->_headersRaw) || count($this->_headers) || (200 != $this->_httpResponseCode)) {
            $this->canSendHeaders();
        } elseif (200 == $this->_httpResponseCode) {
            // Haven't changed the response code, and we have no headers
            return $this;
        }

        $httpCodeSent = false;

        foreach ($this->_headersRaw as $header) {
            if (!$httpCodeSent && $this->_httpResponseCode) {
                header($header, true, $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header);
            }
        }

        foreach ($this->_headers as $header) {
            if (!$httpCodeSent && $this->_httpResponseCode) {
                header($header['name'] . ': ' . $header['value'], $header['replace'], $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
        }

        if (!$httpCodeSent) {
            header('HTTP/1.1 ' . $this->_httpResponseCode);
            $httpCodeSent = true;
        }

		return $this;
    }

	public function cr() {
		return '<br>';
	}

	public function setCachable($minutes) {
		$this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $minutes * 60) . ' GMT', true);
		$this->setHeader('Cache-Control', 'max-age=' . $minutes * 60, true);
		$this->setHeader('Pragma', 'public', true);
	}

	public function setContentType($type='html') {
		switch($type) {
			case 'atom':
				$this->setHeader('content-type', 'application/atom+xml');
				break;
			case 'css':
				$this->setHeader('content-type', 'text/css');
				break;
			case 'gif':
				$this->setHeader('content-type', 'image/gif');
				break;
			case 'jpeg':
			case 'jpg':
				$this->setHeader('content-type', 'image/jpeg');
				break;
			case 'js':
			case 'javascript':
				$this->setHeader('content-type', 'text/javascript');
				break;
			case 'json':
				$this->setHeader('content-type', 'application/json');
				break;
			case 'pdf':
				$this->setHeader('content-type', 'application/pdf');
				break;
			case 'png':
				$this->setHeader('content-type', 'image/png');
			case 'rss':
				$this->setHeader('content-type', 'application/rss+xml');
				break;
			case 'text':
				$this->setHeader('content-type', 'text/plain');
				break;
			case 'xml':
				$this->setHeader('content-type', 'text/xml');
				break;
			case 'html':
			default:
				$this->setHeader('content-type', 'text/html');
				break;
		}
	}

}
