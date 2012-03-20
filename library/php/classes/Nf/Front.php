<?php

namespace Nf;

class Front extends Singleton {

	protected static $_instance;

	// les modules
	private $_moduleDirectories=array();

	// pour le routeur
	private $_routingPreference=array();
	private $_routesDirectory;
	private $_rootRoutesDirectory;
	const rootRouteFilename = '_root.php';
	const controllersDirectory = 'controllers';

	// pour instancier le controller, forwarder...
	private $_moduleNamespace;
	private $_moduleName;
	private $_controllerName;
	private $_actionName;

	// pour le controller
	private $_request;
	private $_response;

	private $_session;

	public static $obLevel=0;

	// the instance of the controller that is being dispatched
	private $_controllerInstance;

	private $_applicationNamespace='App';

	public function __get($var) {
		$varName='_' . $var;
		return $this->$varName;
	}

	public function getModuleName() {
		return $this->_moduleName;
	}

	public function getControllerName() {
		return $this->_controllerName;
	}

	public function getActionName() {
		return $this->_actionName;
	}

	public function setRequest($request) {
		$this->_request=$request;
	}

	public function setResponse($response) {
		$this->_response=$response;
	}

	public function getRequest() {
		return $this->_request;
	}

	public function getResponse() {
		return $this->_response;
	}

	public function setSession($session) {
		$this->_session=$session;
	}

	public function getSession() {
		return $this->_session;
	}

	public function setApplicationNamespace($namespace) {
		$this->_applicationNamespace=$namespace;
	}

	public function getApplicationNamespace() {
		return $this->_applicationNamespace;
	}

	public function getControllerInstance() {
		return $this->_controllerInstance;
	}

// cache
	public function getCache($which) {
		// do we already have the cache object in the Registry ?
		if(Registry::isRegistered('cache_' . $which)) {
			return Registry::get('cache_' . $which);
		}
		else {
			// get the config for our cache object
			$config=Registry::get('config');
			if(isset($config->cache->$which->handler)) {
				$cache=Cache::factory(
										$config->cache->$which->handler,
										(isset($config->cache->$which->params))   ? $config->cache->$which->params   : array(),
										(isset($config->cache->$which->lifetime)) ? $config->cache->$which->lifetime : Cache::DEFAULT_LIFETIME
									);
				return $cache;
			}
			else {
				throw new Exception('The cache handler "' . $which . '" is not set in config file');
			}
		}
	}

// modules

	public function addModuleDirectory($namespace, $dir) {
		$this->_moduleDirectories[]=array('namespace' => $namespace, 'directory' => $dir);
	}

// routes

	public function setRoutesDirectory($path) {
		$this->_routingPreference[]='directory';
        $this->_routesDirectory = $path;
    }

	public function setStructuredRoutes() {
		$this->_routingPreference[]='structured';
	}

	public function setRootRoutes($path) {
		$this->_rootRoutesDirectory = $path;
		$this->_routingPreference[]='root';
	}

	public function findRoute() {

		$foundController=null;
		$uri=$this->_request->getUri();

		// virer tout ce qui est après un éventuel ? qui ne doit pas être pris en compte dans l'url
		$uri = preg_replace('/\?.*$/', '', $uri);

		foreach($this->_routingPreference as $routingPref) {

			unset($_routes);

			if($routingPref=='directory') {
				$subPath = ltrim((string) $uri, '/');

				if (strpos($subPath, '/')) {
		            $subPath = substr($subPath, 0, strpos($subPath, '/'));
		        }

				// on cherche le fichier subPath.php dans le répertoire de routes
				if($subPath!='') {
					$filename=$this->_routesDirectory . $subPath . '.php';

					if(file_exists($filename)) {
						require_once($filename);
						foreach($_routes as $route) {

							// tester si match, sinon on continue jusqu'à ce qu'on trouve
							if(preg_match('#' . $route[0] . '#', $uri, $result)) {
								// on teste la présence du module controller action indiqué dans la route
								if($foundController=$this->checkModuleControllerAction($route[1][0], $route[1][1], $route[1][2])) {
									if(isset($route[2]) && isset($result[1])) {
										$this->associateParams($route[2], $result[1]);
									}
									break;
								}
							}
						}
						unset($route);
					}
				}
			}

			if(!$foundController && $routingPref=='structured') {
				// l'url doit être de la forme /m/c/a/...
				if(preg_match('#^(\w+)/(\w+)/(\w+)#', $uri, $result)) {
					// on regarde si on a un fichier et une action pour le même chemin dans les répertoires des modules
					if($foundController=$this->checkModuleControllerAction($result[1], $result[2], $result[3])) {
						// les éventuels paramètres sont en /variable/value
						$paramsFromUri=ltrim(preg_replace('#^(\w+)/(\w+)/(\w+)#', '', $uri), '/');
						// si on envoie des variables avec des /
						if($paramsFromUri!='') {
							if(substr_count($paramsFromUri, '/')%2==1) {
								preg_match_all('/([a-z0-9_]+)\/([a-z0-9_]*)/i', $paramsFromUri, $arrParams, PREG_SET_ORDER);
								for ($matchi = 0; $matchi < count($arrParams); $matchi++) {
									$this->_request->setParam($arrParams[$matchi][1], $arrParams[$matchi][2]);
								}
							}

							// si on envoie des variables avec des var1=val1
							if(substr_count($paramsFromUri, '=')>=1) {
								preg_match_all('/([a-z0-9_]+)=([^\/&]*)/i', $paramsFromUri, $arrParams, PREG_SET_ORDER);
								for ($matchi = 0; $matchi < count($arrParams); $matchi++) {
									$this->_request->setParam($arrParams[$matchi][1], $arrParams[$matchi][2]);
								}
							}
						}
					}
				}
			}

			if(!$foundController && $routingPref=='root') {
				// on va lire le fichier root
				$filename=$this->_routesDirectory . self::rootRouteFilename;

				if(file_exists($filename)) {
					require_once($filename);
					foreach($_routes as $route) {
						// tester si match, sinon on continue jusqu'à ce qu'on trouve
						if(preg_match('#' . $route[0] . '#', $uri, $result)) {
							// on teste la présence du module controller action indiqué dans la route
							if($foundController=$this->checkModuleControllerAction($route[1][0], $route[1][1], $route[1][2])) {
								if(isset($route[2]) && isset($result[1])) {
									$this->associateParams($route[2], $result[1]);
								}
								break;
							}
						}
					}
					unset($route);
				}
			}
		}

		// si c'est la route par défaut
		if(!$foundController) {
			if(empty($uri)) {
				$config=Registry::get('config');
				if($foundController=$this->checkModuleControllerAction($config->front->default->module, $config->front->default->controller, $config->front->default->action)) {
					if(isset($route[2]) && isset($result[1])) {
						$this->associateParams($route[2], $result[1]);
					}
				}
			}
		}

		return $foundController;
	}

	private function getControllerFilename($directory, $module, $controller) {
		$controllerFilename=ucfirst($controller.'Controller.php');
		return $directory . $module . '/' . self::controllersDirectory . '/' . $controllerFilename;
	}

	private function checkModuleControllerAction($inModule, $inController, $inAction) {
		$foundController=null;

		foreach($this->_moduleDirectories as $moduleDirectory=>$moduleDirectoryInfos) {
			$controllerFilename=$this->getControllerFilename($moduleDirectoryInfos['directory'], $inModule, $inController);
			if(file_exists($controllerFilename)) {
				$this->_moduleNamespace = $moduleDirectoryInfos['namespace'];
				$this->_moduleName = $inModule;
				$this->_controllerName = $inController;
				$this->_actionName = $inAction;
				$foundController=$controllerFilename;
				break;
			}
		}
		unset($moduleDirectory);
		unset($moduleDirectoryInfos);
		if(!$foundController) {
			throw new \Exception('Controller (' . ucfirst($inModule) . '\\' . ucfirst($inController) . 'Controller) was not found in the modules directories');
		}
		return $foundController;
	}

	public function forward($module, $controller, $action) {
		if($foundController=$this->checkModuleControllerAction($module, $controller, $action)) {
			if($this->checkMethodForAction($foundController)) {
				call_user_func(array($this->_controllerInstance, $this->_actionName . 'Action'), null);
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	private function associateParams($routeParams, $refs) {
		if(!is_array($refs)) {
			$refs=array($refs);
		}
		for ($refi = 0; $refi < count($refs); $refi++) {
			if(isset($routeParams[$refi])) {
				$this->_request->setParam($routeParams[$refi], $refs[$refi]);
			}
		}
	}

	public function getView() {
		if(!is_null($this->_controllerInstance->_view)) {
			return $this->_controllerInstance->_view;
		}
		else {
			$config=Registry::get('config');
			$view=View::factory($config->view->engine);
			$view->setResponse($this->_response);
			return $view;
		}

	}

	public function dispatch() {
		// on va regarder le m/c/a concerné par l'url ou les paramètres déjà saisis
		if($foundController=$this->findRoute()) {
			return $this->checkMethodForAction($foundController);
		}
		else {
			return false;
		}
	}

	private function checkMethodForAction($foundController) {
		// on lancera dans l'ordre le init, action, postAction
		require_once($foundController);
		$controllerClassName= $this->_moduleNamespace . '\\' . ucfirst($this->_moduleName) . '\\' . ucfirst($this->_controllerName) . 'Controller';
		$this->_controllerInstance = new $controllerClassName($this);

		$reflected = new \ReflectionClass($this->_controllerInstance);
		if($reflected->hasMethod($this->_actionName . 'Action')) {
			return true;
		}
		else {
			return false;
		}
	}

	// called after dispatch
	public function init() {
		$this->_controllerInstance->init();
	}

	// calls the actual action found from the routing system
	public function launchAction() {
		self::$obLevel = ob_get_level();
        ob_start();

		call_user_func(array($this->_controllerInstance, $this->_actionName . 'Action'), null);

		$content = ob_get_clean();
        $this->_response->addBodyPart($content);
	}

	public static function cleanOutputBuffer() {
		// Clean output buffer on error
        $curObLevel = ob_get_level();
        if ($curObLevel > self::$obLevel) {
            do {
                ob_get_clean();
                $curObLevel = ob_get_level();
            } while ($curObLevel > self::$obLevel);
        }
	}

	// called after action
	public function postAction() {

	}

}