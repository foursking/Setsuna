<?php

namespace Setsuna;

use Setsuna\Router;
use Setsuna\Core\Loader;

use \Exception;


class Application
{
    protected $root;
    public $config = array();
	public $classSubfix = 'Controller';
	public $funcSubfix = 'Action';


    protected $loader;
	protected $router;

    /**
     * Constructor.
     */
    public function __construct($root) {

        $this->loader = new Loader();
        $this->router = new Router();
		$this->root = $root;
		$this->init();
    }

  public function config($config) {
        $this->config = $config;
    }

    /**
     * 初始化
     * @return type
     */
    public function init()
    {
        ob_start();
        session_start();
		$root = $this->root = ($this->root == null) ? dirname(__DIR__) : $this->root;

        // auto require when using class (model)
        spl_autoload_register(function ($classname) use ($root) {
            $fileName = str_replace('\\', '/', $classname) . '.php';
            if (preg_match('/Controller$/', $classname)) {
                $controllerFile = $root.'/controller/'.$fileName;
                return require $controllerFile;
            }
        });

        $this->router->rules($this->config['routers']);
    }

    /**
     * 运行框架
     * @return type
     */
    public function run()
    {
        $reqUri = array_shift(explode('?', $_SERVER['REQUEST_URI']));
        list($call, $param) = $this->router->dispatch($reqUri);
        if (is_array($call)) {
            $class = empty($call[0]) ? 'index'.$this->classSubfix : $call[0].$this->classSubfix;
            $func = empty($call[1]) ? 'index'.$this->funcSubfix : $call[1].$this->classSubfix;
            $c = new $class($this);

            if (method_exists($c, 'init')) {
                $c->init();
            }
            foreach ($param as $key => $value) {
                $c->$key = $value;
            }
			echo $func;
			exit;
			//run it !!!
            return $c->{$func}();
        } else {
			// i dont know
            return $call($param);
        }
    }


    // write file content to dst
    private function _getRequestUri() {
        /* $arr = explode('?', $_SERVER['REQUEST_URI']); */
        /* return $arr[0]; */
		return array_shift(explode('?', $_SERVER['REQUEST_URI']));
    }
}


