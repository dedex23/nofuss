<?php

namespace Nf;

class Bootstrap {

	const DEFAULT_LOCALESELECTIONORDER='cookie,url,browser';

	private $_localeAndVersionFromUrlCache=null;
	private $_applicationNamespace='App';

	public function __construct($libraryPath, $applicationPath) {
		Registry::set('libraryPath', $libraryPath);
		Registry::set('applicationPath', $applicationPath);
	}

	public function initHttpEnvironment($inEnvironment=null, $inLocale=null, $inVersion=null) {

		$urlIni = Ini::parse(Registry::get('applicationPath') . '/configs/url.ini', true);
		Registry::set('urlIni', $urlIni);

		// environment : dev, test, prod
		// si il est défini en variable d'environnement
		if(empty($inEnvironment)) {
			if(getenv('environment')!='') {
				$environment=getenv('environment');
			}
			// sinon on lit le fichier url.ini
			else {
				if(!empty($_SERVER['HTTP_HOST'])) {
					if(preg_match($urlIni->environments->dev->regexp, $_SERVER['HTTP_HOST'])) {
						$environment='dev';
					}
					elseif(preg_match($urlIni->environments->test->regexp, $_SERVER['HTTP_HOST'])) {
						$environment='test';
					}
					else {
						$environment='prod';
					}
				}
				else {
					trigger_error('Cannot guess the requested environment');
				}
			}
		}
		else {
			// aucune vérification pour le moment
			$environment=$inEnvironment;
		}

		// locale
		if(!empty($urlIni->i18n->$environment->localeSelectionOrder)) {
			$localeSelectionOrder=$urlIni->i18n->$environment->localeSelectionOrder;
		}
		else {
			$localeSelectionOrder=self::DEFAULT_LOCALESELECTIONORDER;
		}
		$localeSelectionOrderArray=(array)explode(',', $localeSelectionOrder);
		// 3 possibilities : suivant l'url ou suivant un cookie ou suivant la langue du navigateur (fonctionnement indiqué dans i18n de config.ini)
		if(empty($inLocale)) {
			$locale=null;
			foreach($localeSelectionOrderArray as $localeSelectionMethod) {
				if($locale===null) {
					switch($localeSelectionMethod) {
						case 'browser':
							// on utilise la locale du navigateur et on voit si on a une correspondance
							if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
								// vérification de la syntaxe par une regexp
								if(preg_match('/[a-z]+[_\-]?[a-z]+[_\-]?[a-z]+/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) {
									$locale=strtolower(str_replace('-', '_', $matches[0]));
								}
							}
							break;
						case 'url':
							// lire le fichier url.ini pour connaître la locale à utiliser
							// en fonction de l'url
							// le préfixe détermine la version, le suffixe la locale
							if(!empty($_SERVER['HTTP_HOST'])) {
								$httpHost=strtolower($_SERVER['HTTP_HOST']);
								list($localeFromUrl, $versionFromUrl, $redirectToHost)=$this->getLocaleAndVersionFromUrl($httpHost, $urlIni);
								if(!empty($localeFromUrl)) {
									$locale=$localeFromUrl;
								}
							}
							break;
						case 'cookie':
							// lire le cookie pour connaître la locale à utiliser
							if(!empty($_COOKIE['_nfLc'])) {
								// vérification de la syntaxe par une regexp
								if(preg_match('/[a-z]+[_\-]?[a-z]+[_\-]?[a-z]+/i', $_COOKIE['_nfLc'], $matches)) {
									$locale=strtolower(str_replace('_', '-', $matches[0]));
								}
							}
							break;
					}
				}
			}
		}
		else {
			$locale=$inLocale;
		}
		// if we did not find the locale, we use the default value
		if($locale==null) {
			if(!empty($urlIni->i18n->defaultLocale)) {
				$locale=$urlIni->i18n->defaultLocale;
			}
			else {
				throw new \Exception('You have to set a default locale in url.ini');
			}
		}
		// we match the locale with the defined locale
		$localeFound=false;
		foreach($urlIni->locales as $definedLocale=>$definedLocaleNames) {
			if(strpos($definedLocaleNames, $locale)) {
				$locale=$definedLocale;
				$localeFound=true;
				break;
			}
		}
		// if the detected locale was not found in our defined locales
		if(!$localeFound) {
			// reverting to the default locale
			if(!empty($urlIni->i18n->defaultLocale)) {
				$locale=$urlIni->i18n->defaultLocale;
			}
			else {
				throw new \Exception('You have to set a default locale in url.ini');
			}
		}

		// la version (web, mobile, cli...)
		if(empty($inVersion)) {
			if(!empty($versionFromUrl)) {
				$version=$versionFromUrl;
			}
			else {
				if(in_array('url', $localeSelectionOrderArray)) {
					if(!empty($_SERVER['HTTP_HOST'])) {
						$httpHost=strtolower($_SERVER['HTTP_HOST']);
						list($localeFromUrl, $versionFromUrl, $redirectToHost)=$this->getLocaleAndVersionFromUrl($httpHost, $urlIni);
					}
				}
				if(!empty($versionFromUrl)) {
					$version=$versionFromUrl;
				}
				else {
					// on prend la version par défaut si elle est définie
					if(isset($urlIni->i18n->defaultVersion)) {
						$version=$urlIni->i18n->defaultVersion;
					}
					else {
						trigger_error('Cannot guess the requested version');
					}
				}
			}
		}
		else {
			$version=$inVersion;
		}

		// on assigne les variables d'environnement et de language en registry
		Registry::set('environment', $environment);
		Registry::set('locale', $locale);
		Registry::set('version', $version);

		// on lit le config.ini à la section concernée par notre environnement
		$config = Ini::parse(Registry::get('applicationPath') . '/configs/config.ini', true, $locale . '_' . $environment . '_' . $version);
		Registry::set('config', $config);


		if(!empty($redirectToHost)) {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://" . $redirectToHost . $_SERVER['REQUEST_URI']);
			return false;
		}

		// prevention contre l'utilisation de index.php
		if(isset($_SERVER['REQUEST_URI']) && in_array($_SERVER['REQUEST_URI'], array('index.php','/index.php'))) {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: /");
			return false;
		}

		return true;
	}

	private function getLocaleAndVersionFromUrl($httpHost, $urlIni) {

		$redirectToHost=null;

		if(!empty($this->_localeAndVersionFromUrlCache)) {
			return $this->_localeAndVersionFromUrlCache;
		}
		else {
			$localeFromUrl='';
			$versionFromUrl='';

			$found=false;
			foreach($urlIni->versions as $version_name=>$prefix) {
				if(!$found) {
					$redirectToHost=null;
					foreach($urlIni->suffixes as $locale=>$suffix) {
						if($prefix=='') {
							$httpHostsToTest=array(str_replace('[version]', '', $suffix));
						}
						else {
							if(strpos($prefix, '|')!==false) {
								$prefixes=array_values(explode('|', $prefix));
								$redirectToHost=str_replace('..', '.', ($prefixes[0]=='<>'?str_replace('[version]', '', $suffix):str_replace('[version]', $prefixes[0] . '.', $suffix)));
								foreach($prefixes as $thePrefix) {
									// default empty prefix
									if($thePrefix=='<>') {
										$httpHostsToTest[]=str_replace('..', '.', str_replace('[version]', '', $suffix));
									}
									else {
										$httpHostsToTest[]=str_replace('..', '.', str_replace('[version]', $thePrefix . '.', $suffix));
									}
								}
							}
							else {
								$redirectToHost=null;
								$httpHostsToTest=array(str_replace('..', '.', str_replace('[version]', $prefix, $suffix)));
							}
						}
						// le test sur la chaîne reconstruite
						foreach($httpHostsToTest as $httpHostToTest) {
							if($httpHost==$httpHostToTest) {
								$localeFromUrl=$locale;
								$versionFromUrl=$version_name;
								if($locale=='_default') {
									$localeFromUrl=$urlIni->i18n->defaultLocale;
								}
								if($httpHostToTest==$redirectToHost) {
									$redirectToHost=null;
								}
								$found=true;
								break;
							}
						}
					}
				 }
				 else {
				 	break;
				 }
				unset($suffix);
			}
			unset($prefix);
			$this->_localeAndVersionFromUrlCache=array($localeFromUrl, $versionFromUrl, $redirectToHost);
		}

		return array($localeFromUrl, $versionFromUrl, $redirectToHost);

	}

	public function setApplicationNamespace($namespace) {
		$this->_applicationNamespace=$namespace;
		\Nf\Registry::set('applicationNamespace', $namespace);
	}

	public function initCliEnvironment() {

		$showUsage=true;

		if (isset($_SERVER['argv']) && $_SERVER['argc'] >= 2) {

			$inEnvironment='dev';
			$inLocale='en';
			$inVersion='cli';
			$inAction=null;

			$arrParams=array();

			$ac = 1;
			while ($ac < (count($_SERVER['argv']))) {

				switch($_SERVER['argv'][$ac]) {
					case '-e':
					case '--environment':
					 	$inEnvironment=$_SERVER['argv'][$ac+1];
					 	$ac+=2;
					 	break;
					case '-l':
					case '--locale':
					 	$inLocale=$_SERVER['argv'][$ac+1];
					 	$ac+=2;
					 	break;
					case '-v':
					case '--version':
					 	$inVersion=$_SERVER['argv'][$ac+1];
					 	$ac+=2;
					 	break;
					case '-a':
					case '--action':
					 	$inAction=$_SERVER['argv'][$ac+1];
					 	$ac+=2;
					 	$showUsage=false;
					 	break;
					default:
						$ac+=2;
						break;
				}
			}

			// chaînes possibles
			// module/controller/action/variable1/valeur1/variable2/valeur2
			// on vérifie qu'on a au moins 2 slashes
			/*
			if(mb_substr_count($inAction, '/')>=2) {

				if(substr($inAction, 0, 1)=='/') {
					$inAction = substr($inAction, 1);
				}
				// on enlève le trailing slash si présent
				if(substr($inAction, -1, 1)=='/') {
					$inAction = substr($inAction, 0, strlen($inAction)-1);
				}

				$showUsage=false;

				preg_match_all('/([a-z0-9]+)\/([a-z0-9]+)\/([a-z0-9]+)\/?(.*)/i', $inAction, $query, PREG_PATTERN_ORDER);
				if(count($query)>=3) {

					$module=$query[1][0];
					$controller=$query[2][0];
					$action=$query[3][0];

					if(!empty($query[4][0])) {
						$params=$query[4][0];

						// on enlève le premier point d'interrogation si présent
						if(mb_substr($params, 0, 1)=='?') {
							$params=mb_substr($params, 1);
						}

						// si on envoie des variables avec des var1=val1
						if(mb_substr_count($params, '=')>=1) {
							preg_match_all('/([a-z0-9_]+)=([^\/&]*)/i', $params, $arrParamsSlash, PREG_SET_ORDER);
							for ($matchi = 0; $matchi < count($arrParamsSlash); $matchi++) {
								$arrParams[$arrParamsSlash[$matchi][1]] = $arrParamsSlash[$matchi][2];
							}
						}
						else {
							// si on envoie des variables avec des /
							if(mb_substr_count($params, '/')%2==1) {
								preg_match_all('/([a-z0-9_]+)\/([a-z0-9_]*)/i', $params, $arrParamsSlash, PREG_SET_ORDER);
								for ($matchi = 0; $matchi < count($arrParamsSlash); $matchi++) {
									$arrParams[$arrParamsSlash[$matchi][1]] = $arrParamsSlash[$matchi][2];
									//$params=str_replace($params, $arr_params_slash[$matchi][1] . '/' . $arr_params_slash[$matchi][2], '');
								}
							}
						}
					}
					else {
						$params=array();
					}
				}
			}
			*/
		}

		if(!$showUsage) {

			// on lit le config.ini à la section concernée par notre environnement
			$config = Ini::parse(Registry::get('applicationPath') . '/configs/config.ini', true, $inLocale . '_' . $inEnvironment . '_' . $inVersion);
			Registry::set('config', $config);

			// on assigne les variables d'environnement et de language en registry
			Registry::set('environment', $inEnvironment);
			Registry::set('locale', $inLocale);
			Registry::set('version', $inVersion);

			return $inAction;

		}
		else {
			echo "Usage : module/controller/action";
			echo "\nOr : module/controller/action/variable1/valeur1/variable2/valeur2/variable3/valeur3";
			echo "\nOr : \"module/controller/action?variable1=valeur1&variable2=valeur2&variable3=valeur3\"";
			echo "\nUse quotes (\") if you use the usual method ?var=value\n";
			exit (04);
		}

	}

	function redirectForUserAgent(){
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$userAgent=new UserAgent($_SERVER['HTTP_USER_AGENT']);
			// check the [redirections] section of the url.ini against the userAgent and redirect if we've been told to
			$urlIni=Registry::get('urlIni');
			foreach($urlIni->redirections as $class => $forcedVersion) {
				if($userAgent->checkClass($class)) {
					if(!empty($forcedVersion)) {
						// get the redirection URL according to the current class
						$suffixes=(array)$urlIni->suffixes;
						$versions=(array)$urlIni->versions;
						if($forcedVersion!=$this->_localeAndVersionFromUrlCache[1]) {
							$redirectionUrl='http://' . str_replace('[version]', $versions[$forcedVersion], $suffixes[$this->_localeAndVersionFromUrlCache[0]]);
							$response = new Front\Response\Http();
							$response->redirect($redirectionUrl, 301);
							$response->sendHeaders();
							return true;
						}
					}
				}
			}
		}
		return false;
	}



	function go(){
		if(php_sapi_name()=='cli') {

			$uri=$this->initCliEnvironment();
			Error\Handler::setErrorDisplaying();
			$front=Front::getInstance();

			$request = new Front\Request\Cli($uri);
			$front->setRequest($request);

			$response = new Front\Response\Cli();

			$front->setResponse($response);
			$front->setApplicationNamespace($this->_applicationNamespace);

			// routing
			$front->setStructuredRoutes();

			$front->addModuleDirectory($this->_applicationNamespace, Registry::get('applicationPath') . '/application/cli/');
			$front->addModuleDirectory('library', Registry::get('libraryPath') . '/php/application/cli/');

			$labelManager=LabelManager::getInstance();
			$labelManager->loadLabels(Registry::get('locale'));

			$testDispatch=$front->dispatch();

			if($testDispatch) {
				$front->init();
				$front->launchAction();
				$front->postAction();
				$response->sendResponse();
			}
			else {
				throw new \Exception ('Action not found : ' . $uri);
			}

		}
		else {
			$this->initHttpEnvironment();
			Error\Handler::setErrorDisplaying();

			if(!$this->redirectForUserAgent()) {

				$front=Front::getInstance();
				$request = new Front\Request\Http();

				$front->setRequest($request);
				$response = new Front\Response\Http();
				$front->setResponse($response);
				$front->setApplicationNamespace($this->_applicationNamespace);

				// routing
				$front->setRoutesDirectory(Registry::get('applicationPath') . '/routes/' . Registry::get('version') . '/',  Registry::get('locale'));
				$front->setRootRoutes(Registry::get('applicationPath') . '/routes/' . Registry::get('version') . '/', Registry::get('locale'));
				$front->setStructuredRoutes();

				$front->addModuleDirectory($this->_applicationNamespace, Registry::get('applicationPath') . '/application/' . Registry::get('version') . '/');
				$front->addModuleDirectory('library', Registry::get('libraryPath') . '/php/application/' . Registry::get('version') . '/');

				$front->setSession(Session::start());

				$labelManager=LabelManager::getInstance();
				$labelManager->loadLabels(Registry::get('locale'));

				$testDispatch=$front->dispatch();

				$requestIsClean=$request->sanitizeUri();

				if($requestIsClean) {
					if($testDispatch===true) {
						if(!$request->redirectForTrailingSlash()) {
							$front->init();
							if(!$front->response->isRedirect())
								$front->launchAction();
							if(!$front->response->isRedirect())
								$front->postLaunchAction();
							$response->sendResponse();
						}
					}
					else {
						Error\Handler::handleNotFound(404);
					}
				}
				else {
					Error\Handler::handleForbidden(403);
				}
			}
		}
	}

}