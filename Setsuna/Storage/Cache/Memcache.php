<?php

namespace Setsuna\Storage\Cache;
use Setsuna\Storage\Cache\CacheInterface;
use \Memcache;



class MemcacheCache implements CacheInterface
{
	//Memcache对象
	private $_memcache = null;


	public function getInstance() {

		if (!extension_loaded('memcache')) {
			return '';
		}

		if(empty($this->_memcache)){
			$this->_memcache = new Memcache();
			$this->_memcache->addServer('localhost' , 11211);
		}
		return $this->_memcache;
	}


	public function getCache() {
		return empty($this->_memcache) ? $this->getInstance() : $this->_memcache;
	}


	public function set( $key , $value , $time = 0) {
		return $this->getCache()->set(md5($key) , $value );
	}

	public function get( $key ) {
		return $this->getCache()->get(md5($key));
	}


	public function remove($key) {
		return $this->getCache()->delete(md5($key), 0);
	}

	public function getMultiple(array $keys) { 

	}

	function __destruct() {
		$this->_memcache->close();
	}


}
