<?php

namespace Setsuna\Storage\Cache;
use Setsuna\Storage\Cache\CacheInterface;
use \Redis;


/**
 * Redis 
 * 
 * @uses CacheInterface
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com> 
 * @license GNU General Public License 2.0
 */
class RedisCache implements CacheInterface 
{
    /**
     * redis对象
     *
     * @var \Redis
     */
    private $_redis = null;

    /**
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     * @param int    $db
     */
    public function __construct($host = 'localhost', $port = 6379, $timeout = 30, $db = 0)
    {
        $this->_redis = new \Redis();
        $this->_redis->connect($host, $port, $timeout);
        $this->_redis->select($db);
    }


	public function getInstance() {

		if (!extension_loaded('redis')) {
			return '';
		}

		if(empty($this->_redis)){
			$this->_redis = new Redis();
			$this->_redis->connect($host , $prot , $timeout);
		}
		return $this->_redis;
	}


	public function getCache() {
		return empty($this->_redis) ? $this->getInstance() : $this->_redis;
	}




    /**
     * 设置缓存
     *
     * @param string $key
     * @param array  $data
     */
    public function setHash($key, array $data)
    {
        $this->getCache()->hMSet($key, $data);
    }

    /**
     * 获取缓存
     *
     * @param string $key
     * @return mixed
     */
    public function getHash($key)
    {
        return $this->getCache()->hGetAll($key);
    }

    /**
     * 获取多个缓存
     *
     * @param array $keys
     * @return array
     */
    public function getMultipleHash(array $keys) {
        $pipeline = $this->getCache()->pipeline();
        foreach ($keys as $key) {
            $pipeline->hGetAll($key);
        }

        return $pipeline->exec();
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function removeHash($key) {
        return $this->getCache()->delete($key);
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param string $data
     */
    public function set($key, $data) {
        return $this->getCache()->set($key, $data);
    }

    /**
     * 获取缓存
     *
     * @param string $key
     * @return string
     */
    public function get($key) {
        return $this->getCache()->get($key);
    }

    /**
     * 获取多个缓存
     *
     * @param array $keys
     * @return array
     */
    public function getMultiple(array $keys) {
        $pipeline = $this->getCache()->pipeline();
        foreach ($keys as $key) {
            $pipeline->get($key);
        }
        return $pipeline->exec();
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function remove($key) {
        $this->getCache()->delete($key);
    }
  
}

