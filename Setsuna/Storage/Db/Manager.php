<?php

class Manager {
	// 生成存储服务实例之前
	const BEFORE_CREATE_INSTANCE_EVENT = 'before create instance event';
	// 生成存储服务实例之后
	const AFTER_CREATE_INSTANCE_EVENT = 'after create instance event';

	static private $instance;

	static public $config_path = array('storages');

	private $config;

	private $storages = array();

	private $dispatcher = array();

	protected function __construct() {
		if (!$this->config = \Lysine\Config::get(self::$config_path))
			throw new Storage\Error('Storages config not found! Current Path: ['. implode(',', self::$config_path) .']');
	}

	// 获得指定存储服务的配置信息
	// return array or false
	private function getConfig($name) {
		if (!isset($this->config[$name])) return false;

		$config = $this->config[$name];
		if (!isset($config['__IMPORT__'])) return $config;

		if (!$import_config = $this->getConfig($config['__IMPORT__']))
			throw Storage\Error::undefined_storage($config['__IMPORT__']);

		$config = array_merge($import_config, $config);
		unset($config['__IMPORT__']);

		return $config;
	}

	// 获得指定的存储服务连接实例
	// return IStorage
	public function get($name = null, $args = null) {
		if ($name === null) $name = '__default__';

		if (isset($this->dispatcher[$name])) {
			$dispatcher_name = $name;
			$callback = $this->dispatcher[$dispatcher_name];
			$name = ($args === null)
				? call_user_func($callback)
				: call_user_func_array(
					$callback,
					is_array($args) ? $args : array_slice(func_get_args(), 1)
				);
			if ($name === null)
				throw new Storage\Error('Storage dispatcher ['. $dispatcher_name .'] not return a storage name');
		}

		if (isset($this->storages[$name])) return $this->storages[$name];

		if (!$config = $this->getConfig($name))
			throw Storage\Error::undefined_storage($name);

		fire_event($this, self::BEFORE_CREATE_INSTANCE_EVENT, array($name, $config));

		$class = $config['class'];
		unset($config['class']);
		$storage = new $class($config);

		fire_event($this, self::AFTER_CREATE_INSTANCE_EVENT, array($storage, $name, $config));

		return $this->storages[$name] = $storage;
	}

	// 设置存储路由方法
	// return self
	public function setDispatcher($name, $callback) {
		if (!is_callable($callback))
			throw Storage\Error::not_callable("Storage dispatcher ${name}");
		$this->dispatcher[$name] = $callback;
		return $this;
	}

	// 单例
	// return Storage\Manager
	static public function instance() {
		return self::$instance ?: (self::$instance = new static);
	}
}

