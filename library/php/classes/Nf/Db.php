<?php

namespace Nf;

class Db {

	const FETCH_ASSOC = 2;
    const FETCH_NUM = 3;
    const FETCH_OBJ = 5;
    const FETCH_COLUMN = 7;

	private static $_connections=array();

	private static function factory($config) {

		if (!is_array($config)) {
        	// convert to an array
        	$conf=array();
        	$conf['adapter']=$config->adapter;
        	$conf['params']=(array)$config->params;
        }
        else {
			$conf=$config;
		}
        $adapterName=get_class() . '\\Adapter\\' . $conf['adapter'];
        $dbAdapter = new $adapterName($conf['params']);
        return $dbAdapter;
	}

	public static function getConnection($configName, $alternateHostname=null, $alternateDatabase=null, $storeInInstance=true) {

		$config=\Nf\Registry::get('config');

		$defaultHostname=$config->db->$configName->params->hostname;
		$defaultDatabase=$config->db->$configName->params->database;
		$hostname=($alternateHostname!==null)?$alternateHostname:$defaultHostname;
		$database=($alternateDatabase!==null)?$alternateDatabase:$defaultDatabase;

		// if the connection has already been created and if we store the connection in memory for future use
		if(isset(self::$_connections[$configName . '-' . $hostname . '-' . $database]) && $storeInInstance) {
			return self::$_connections[$configName . '-' . $hostname . '-' . $database];
		}
		else {

			// or else we create a new connection
			$dbConfig=array(
				'adapter' => $config->db->$configName->adapter,
				'params' => array(
					'hostname' => $hostname,
					'username' => $config->db->$configName->params->username,
					'password' => $config->db->$configName->params->password,
					'database' => $database,
					'charset' => $config->db->$configName->params->charset
				)
			);

			// connexion d'après les infos du fichier de config
			$dbConnection=self::factory($dbConfig);
			if($storeInInstance) {
				self::$_connections[$configName . '-' . $hostname . '-' . $database]=$dbConnection;
			}
			return $dbConnection;
		}
	}


}