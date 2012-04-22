<?php

namespace Nf;

class LabelManager extends Singleton {

	protected static $_instance;

	private $_labelsLoaded=false;
	private $_labels=array();

	// load the labels
	public function loadLabels($locale) {
		if(!$this->_labelsLoaded) {
			$this->_labels=parse_ini_file(\Nf\Registry::get('applicationPath') . '/labels/' . $locale . '.ini', true);
			$this->_labelsLoaded=true;
		}
	}

	public static function get($lbl) {
		$instance=self::$_instance;
		return $instance->_labels[$lbl];
	}

	public static function getAll($section=null) {
		$instance=self::$_instance;
		if($section!=null) {
			return $instance->_labels[$section];
		}
		else {
			return $instance->_labels;
		}
	}

	public function __get($lbl) {
		return $this->_labels[$lbl];
	}

}
