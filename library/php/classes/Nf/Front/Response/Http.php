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

    public function redirect($url, $code = 302) {
        $this->canSendHeaders();
        $this->setHeader('Location', $url, true)
             ->setHttpResponseCode($code);
    	print_r($this->_headers);
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
            // require_once 'Zend/Controller/Response/Exception.php';
            throw new Exception('Invalid HTTP response code');
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

}
