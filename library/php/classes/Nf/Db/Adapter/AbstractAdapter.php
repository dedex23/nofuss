<?php

namespace Nf\Db\Adapter;

abstract class AbstractAdapter
{

    protected $_config = array();

    protected $_connection = null;
	
	protected $_autoQuoteIdentifiers = true;

    public function __construct($config) {
        if (!is_array($config)) {
			throw new \Exception('Adapter parameters must be in an array');
		}
        if (!isset($config['charset'])) {
            $config['charset'] = null;
        }
        $this->_config = $config;
    }
	
	public function getConnection() {
        $this->_connect();
        return $this->_connection;
    }

    public function query($sql) {
        $this->_connect();
        $res = new $this->_resourceClass($sql, $this);
        $res->execute();
        return $res;
    }
	
	public function fetchAll($sql) {
		$stmt = $this->query($sql);
        $result = $stmt->fetchAll();
        return $result;
	}
	
	public function fetchRow($sql) {
        $stmt = $this->query($sql);
        $result = $stmt->fetch();
        return $result;
    }
	
	public function fetchCol($sql) {
        $stmt = $this->query($sql);
        $result = $stmt->fetchAll(\Nf\Db::FETCH_COLUMN, 0);
        return $result;
    }
	
	public function fetchOne($sql) {
        $stmt = $this->query($sql);
        $result = $stmt->fetchColumn(0);
        return $result;
    }
	
	public function fetchPairs($sql) {
        $stmt = $this->query($sql);
        $data = array();
        while ($row = $stmt->fetch(\Nf\Db::FETCH_NUM)) {
            $data[$row[0]] = $row[1];
        }
        return $data;
    } 
	
	public function beginTransaction() {
		$this->_beginTransaction();
		return $this;
	}
	
	public function commit() {
		$this->_commit();
		return $this;
	}
	
	public function rollback() {
		$this->_rollback();
		return $this;
	}
	

	

    protected function _quote($value) {
        if (is_int($value)) {
            return $value;
        }
		elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        else {
        	return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
        }
    }

    public function quote($value, $type = null) {
        $this->_connect();
        return $this->_quote($value);
    }

    public function quoteIdentifier($ident, $auto=false) {
        return $this->_quoteIdentifierAs($ident, null, $auto);
    }

    public function quoteColumnAs($ident, $alias, $auto=false) {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    protected function _quoteIdentifierAs($ident, $alias = null, $auto = false, $as = ' AS ') {
        if (is_string($ident)) {
            $ident = explode('.', $ident);
        }
        if (is_array($ident)) {
            $segments = array();
            foreach ($ident as $segment) {
            	$segments[] = $this->_quoteIdentifier($segment, $auto);
            }
            if ($alias !== null && end($ident) == $alias) {
                $alias = null;
            }
            $quoted = implode('.', $segments);
        } else {
            $quoted = $this->_quoteIdentifier($ident, $auto);
        }

        if ($alias !== null) {
            $quoted .= $as . $this->_quoteIdentifier($alias, $auto);
        }
        return $quoted;
    }

    protected function _quoteIdentifier($value, $auto=false) {
        if ($auto === false || $this->_autoQuoteIdentifiers === true) {
            $q = $this->getQuoteIdentifierSymbol();
            return ($q . str_replace("$q", "$q$q", $value) . $q);
        }
        return $value;
    }

    public function getQuoteIdentifierSymbol() {
        return '"';
    }

    abstract protected function _connect();

    abstract public function isConnected();

    abstract public function closeConnection();

    abstract public function lastInsertId($tableName = null, $primaryKey = null);

}
