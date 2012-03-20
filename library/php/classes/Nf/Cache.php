<?php

namespace Nf;

abstract class Cache
{

	// default lifetime for any stored value
	const DEFAULT_LIFETIME=600;

	public static function getKeyName($keyName, $keyValues) {
		$config=Registry::get('config');
		if(!isset($config->cachekeys->$keyName)) {
			throw new Exception('Key ' . $keyName . ' is not set in the config file.');
		}
		else {
			$configKey=$config->cachekeys->$keyName;
			if(is_array($keyValues)) {
				// if we send an associative array
				if($this->is_assoc($keyValues)) {
					$result=$configKey;
					foreach($keyValues as $k=>$v) {
						$result=str_replace('[' . $k . ']', $v, $result);
					}
				}
				// if we send an indexed array
				else {
					preg_match_all('/\[([^\]]*)\]/', $configKey, $vars, PREG_PATTERN_ORDER);
					if(count($vars[0]) != count($keyValues)) {
						throw new Exception('Key ' . $keyName . ' contains a different number of values than the keyValues you gave.');
					}
					else {
						$result=$configKey;
						for ($i = 0; $i < count($vars[0]); $i++) {
							$result=str_replace('[' . $vars[0][$i] . ']', $keyValues[$i]);
						}
					}
				}
			}
			else {
				// if we send only one value
				$result = preg_replace('/\[([^\]]*)\]/', $keyValues, $configKey);
			}
		}
		// s'il reste des [ dans la cle, c'est qu'on n'a pas envoye les bonnes valeurs dans key_values
		if(strpos($result, '[')) {
			throw new Exception('The cache key ' . $keyName . ' cannot be parsed with the given keyValues.');
		}
		else {
			$keyPrefix=!empty($config->cache->keyPrefix)?$config->cache->keyPrefix:'';
			return $keyPrefix . $result;
		}
	}

	public static function isCacheEnabled() {
		return isset($config->cache->enabled)?(bool)$config->cache->enabled:true;
	}

	public static function factory($type, $params, $lifetime=DEFAULT_LIFETIME) {
		$className=get_class() . '\\' . ucfirst($type);
		return new $className($params, $lifetime);
	}

}