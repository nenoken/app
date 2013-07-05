<?php

/**
 * UIFactory handles building component which means loading
 * assets and component configuration file
 *
 * @author Andrzej Łukaszewski <nandy@wikia-inc.com>
 * @author Bartosz Bentkowski <bartosz.bentkowski@wikia-inc.com>
 *
 */

class UIFactory {

	/**
	 * @desc Component's configuration file suffix
	 * Example buttons component config file should be named buttons_config.json
	 *
	 * @var String
	 */
	const CONFIG_FILE_SUFFIX = '_config.json';

	/**
	 * @desc Component's directory path from docroot
	 */
	const DEFAULT_COMPONENTS_PATH = "/resources/wikia/ui_components/";

	/**
	 * @desc Component's template directory's name
	 */
	const TEMPLATES_DIR_NAME = 'templates';

	/**
	 * @desc How long are components memcached? Both specific and all (different memc keys)
	 */
	const MEMCACHE_EXPIRATION = 900; // 15 minutes

	/**
	 * @desc Version passed to memcache key
	 */
	const MEMCACHE_VERSION = '1.0';
	
	/**
	 * @var UIFactory
	 */
	private static $instance = null;

	/**
	 * @desc AssetsManager/ResourceLoader
	 * @var $loaderService
	 */
	private $loaderService;

	/**
	 * @desc Path to components directory
	 * @var String|null
	 */
	private $componentsDir = null;

	/**
	 * @desc Private constructor because it's a singleton
	 */
	public function __construct() {
		global $IP;
		$this->componentsDir = $IP . self::DEFAULT_COMPONENTS_PATH;
		$this->loaderService = AssetsManager::getInstance();
	}

	/**
	 * @desc Sets the path to components' directory
	 *
	 * @param String $path
	 */
	public function setComponentsDir( $path ) {
		$this->componentsDir = $path;
	}

	/**
	 * @desc Returns the path to component's directory
	 *
	 * @return null|String
	 */
	public function getComponentsDir() {
		return $this->componentsDir;
	}

	/**
	 * @desc Returns the only instnace of the class; singleton
	 *
	 * @return UIFactory
	 */
	static public function getInstance() {
		if( is_null(static::$instance) ) {
			static::$instance = new self();
		}
		
		return static::$instance;
	}

	/**
	 * @desc Returns full file path
	 *
	 * @param string $name component's name
	 *
	 * @returns string full file path
	 */
	public function getComponentConfigFileFullPath( $name ) {
		return $this->getComponentsDir() . $name . '/' . $name . self::CONFIG_FILE_SUFFIX;
	}

	/**
	 * @desc Loads UIComponent from given string
	 *
	 * @param string $configContent JSON String
	 *
	 * @return UIComponent
	 *
	 * @throws Exception
	 */
	private function loadComponentConfigFromJSON( $configContent ) {
		$config = json_decode( $configContent, true );

		if ( !is_null( $config ) ) {
			return $this->addComponentsId( $config );
		} else {
			throw new Exception( 'Invalid JSON.' );
		}
	}

	/**
	 * @desc Loads UIComponent from file
	 *
	 * @param string $configFilePath Path to file
	 *
	 * @return UIComponent
	 *
	 * @throws Exception
	 */
	public function loadComponentConfigFromFile( $configFilePath ) {
		if ( false === $configString = file_get_contents( $configFilePath ) ) {
			throw new Exception( 'Component\'s config file not found.' );
		} else {
			return $this->loadComponentConfigFromJSON( $configString );
		}
	}

	/**
	 * @desc Gets configuration file contents, decodes it to array and returns it
	 * 
	 * @param String $componentName
	 * @return string
	 */
	protected function loadComponentConfig( $componentName ) {
		wfProfileIn( __METHOD__ );
		
		global $wgMemc;
		
		$memcKey = wfMemcKey( __CLASS__, 'component', $componentName, static::MEMCACHE_VERSION);
		$data = $wgMemc->get( $memcKey );

		if ( !empty($data) ) {
			wfProfileOut( __METHOD__ );
			return $data;
		} else {
			$configFile = $this->getComponentConfigFileFullPath( $componentName );
			$config = $this->loadComponentConfigFromFile( $configFile );
			$wgMemc->set( $memcKey, $config, self::MEMCACHE_EXPIRATION );

			wfProfileOut( __METHOD__ );
			return $config;
		}
	}

	/**
	 * @desc Adds id element to the component's config array
	 *
	 * @param Array $componentCfg
	 * @return array
	 */
	private function addComponentsId( $componentCfg ) {
		$componentCfg['id'] = static::sanitize( $componentCfg['name'] );

		return $componentCfg;
	}

	/**
	 * @desc Adds asset to load
	 *
	 * @param $assetName
	 */
	// TODO think how we can use WikiaResponse addAsset method
	// TODO during work on UIComponent in darwin sprint 13
	private function addAsset( $assetName ) {
		wfProfileIn( __METHOD__ );

		$app = F::app();
		$jsMimeType = $app->wg->JsMimeType;

		$type = false;

		$sources = $this->loaderService->getURL( $assetName, $type, false );

		foreach ( $sources as $source ) {
			switch ( $type ) {
				case AssetsManager::TYPE_CSS:
				case AssetsManager::TYPE_SCSS:
					$app->wg->Out->addStyle( $source );
					break;
				case AssetsManager::TYPE_JS:
					$app->wg->Out->addScript( "<script type=\"{$jsMimeType}\" src=\"{$source}\"></script>" );
					break;
			}
		}

		wfProfileOut( __METHOD__ );
	}

	public function getComponentsBaseTemplatePath( $name ) {
		$name = static::sanitize( $name );
		return $this->getComponentsDir() .
			$name .
			DIRECTORY_SEPARATOR .
			self::TEMPLATES_DIR_NAME .
			DIRECTORY_SEPARATOR .
			$name;
	}
	
	/**
	 * @desc Loads JS/CSS dependencies, creates and configurates an instance of UIComponent object which is returned
	 *
	 * @param string|array
	 */
	public function init( $componentNames ) {
		if ( !is_array($componentNames ) ) {
			$componentNames = (array)$componentNames;
		}
		
		$components = [];
		$assets = [];

		// iterate $componentNames, read configs, write down dependencies
		foreach ( $componentNames as $name ) {
			$componentConfig = $this->loadComponentConfig( $name );
			
			// if there are some components, put them in the $assets
			if ( !empty( $componentConfig['dependencies'] ) ) {
				if ( !empty( $componentConfig['dependencies']['js'] ) ) {
					$assets = array_merge( $assets, $componentConfig['dependencies']['js'] );
				}
				if ( !empty( $componentConfig['dependencies']['css'] ) ) {
					$assets = array_merge( $assets, $componentConfig['dependencies']['css'] );
				}
			}

			// init component, put config inside and set base template path
			$component = $this->getComponentInstance();
			if ( !empty($componentConfig['templateVars']) ) {
				$component->setTemplateVarsConfig( $componentConfig['templateVars'] );
			}
			$component->setBaseTemplatePath( $this->getComponentsBaseTemplatePath( $name ) );

			$components[] = $component;
		}

		// add merged assets
		foreach ( array_unique( $assets ) as $asset ) {
			$this->addAsset( $asset );
		}

		// return components
		return (sizeof($components) == 1) ? $components[0] : $components;
	}
	
	protected function getComponentInstance() {
		return new UIComponent();
	}
	
	public static function sanitize( $string ) {
		return str_replace( ' ', '_', mb_strtolower( $string ) );
	}

	/**
	 * @throws Exception
	 */
	public function __clone() {
		throw new Exception( 'Cloning instances of this class is forbidden.' );
	}

	/**
	 * @throws Exception
	 */
	public function __wakeup() {
		throw new Exception( 'Unserializing instances of this class is forbidden.' );
	}
}

