<?php

namespace Setsuna\Core;
use Setsuna\Core\Loader;
use Setsuna\Router\Router;
use \Exception;


class Application
{ 
	public $config = array();
	public $classSubfix = 'Controller';
	public $funcSubfix = 'Action';
	protected $root;
	protected $loader;
	protected $router;

	/**
	 * Constructor.
	 */
	public function __construct() {

	}

	/**
	 * 初始化
	 * @return type
	 */
	public function init($container)
	{
		$this->loader = new Loader();
		$this->router = new Router();
		$this->root = $container['APP_ROOT'];
		$root = $this->root = ($this->root == null) ? dirname(__DIR__) : $this->root;

		// auto require when using class (model)
		spl_autoload_register(function ($classname) use ($root) {
			$fileName = str_replace('\\', '/', $classname) . '.php';
			if (preg_match('/Controller$/', $classname)) {
				$controllerFile = $root.'/controller/'.$fileName;
				require $controllerFile;
			}
		});


		//init github
		$container['github'] = $container->share(function(){
			return new \Github\Client();
		});

		//init loader
		$container['Loader'] = $container->share(function() {
			return new Loader();
		
		});


		//init router
		$container['Router'] = $container->share(function() {
			return new Router();
		});
		


		return $container;
	}



	/**
	 * @return type
	 */

	public function run($container) {

		$reqUri = array_shift(explode('?', $_SERVER['REQUEST_URI']));
		list($call, $param) = $container['Router']->dispatch($reqUri);
		if (is_array($call)) {
			$class = empty($call[0]) ? 'index'.$this->classSubfix : $call[0].$this->classSubfix;
			$func = empty($call[1]) ? 'index'.$this->funcSubfix : $call[1].$this->funcSubfix;

			$c = new $class($container);

			if (method_exists($c, 'init')) {
				$c->init();
			}
			foreach ($param as $key => $value) {
				$c->$key = $value;
			}
			//run it !!!
			return $c->{$func}();
		} else {
			// i dont know
			return $call($param);
		}
	}
}


