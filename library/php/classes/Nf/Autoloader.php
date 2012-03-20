<?php

/**
 * Autoloader is a class loader.
 *
 *     <code>
 *      require($library_path . '/php/classes/Nf/Autoloader.php');
 *		$autoloader=new \Nf\Autoloader();
 *		$autoloader->setMap($application_path);
 * 		$autoloader->addNamespace('Nf', $library_path . '/Nf', '.php');
 * 		$autoloader->register();
 *     </code>
 *
 * @package default
 * @author Julien Ricard
 * @copyright This software is open source protected by the FreeBSD License.
 * @version 1.0
 **/

namespace Nf;

class Autoloader {

	protected static $_directories=Array();
	protected static $_map=null;
	protected static $_namespaceSeparator='\\';
	const defaultSuffix='.php';

	public static function load($className) {

		if(!class_exists($className)) {

			if(self::$_map!=null) {
				// reads the map for getting class path

			}
			else {
				$namespaceRoot='';
				$fileNamePrefix='';

				// reads each directory until it finds the class file
				if (false !== ($lastNsPos = strripos($className, self::$_namespaceSeparator))) {
	                $namespace = substr($className, 0, $lastNsPos);
	                $namespaceRoot = substr($className, 0, strpos($className, self::$_namespaceSeparator));
	                $shortClassName = substr($className, $lastNsPos + 1);
	                $fileNamePrefix = str_replace(self::$_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	            }
	            else {
					$shortClassName = $className;
				}
				$fileNamePrefix .= str_replace('_', DIRECTORY_SEPARATOR, $shortClassName);

				// if the class uses a namespace
				if($namespaceRoot!='') {
					// and if we have a specified directory for this namespace's root
					foreach(self::$_directories as $directory) {
						if($directory['namespaceRoot']==$namespaceRoot) {
							// use the specified directory with remaining path
							$fileNamePrefix = str_replace($namespaceRoot . DIRECTORY_SEPARATOR, '', $fileNamePrefix);
							if(self::includeClass($directory['path'] . $fileNamePrefix . $directory['suffix'], $className)) {
								return true;
							}
							else {
								// file was not found in the specified directory
								return false;
							}
						}
					}
				}
				else {
					foreach(self::$_directories as $directory) {
						if(self::includeClass($directory['path'] . $fileNamePrefix . $directory['suffix'], $className)) {
							return true;
						}
					}
				}
			}
		}
		else {
			return true;
		}
		return false;
	}

	public static function includeClass($file, $class_name) {
		if(!class_exists($class_name)) {
			if(file_exists($file)) {
				require $file;
				return true;
			}
			else {
				return false;
			}
		}
		else {
			echo 'class exists';
		}
	}

	public static function addNamespaceRoot($namespaceRoot, $path, $suffix=self::defaultSuffix) {
		if(substr($path, -1)!=DIRECTORY_SEPARATOR) {
			$path.=DIRECTORY_SEPARATOR;
		}
		self::$_directories[]=array('namespaceRoot' => $namespaceRoot, 'path' => $path, 'suffix' => $suffix);
	}

	public static function setMap($map_file_path) {
		if(file_exists($map_file_path)) {
			self::$_map=file_get_contents($map_file_path);
		}
	}

	public function register() {
		spl_autoload_register(__NAMESPACE__ . '\Autoloader::load');
	}
}
