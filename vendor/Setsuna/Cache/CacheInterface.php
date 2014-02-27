<?php

namespace Setsuna\Cache;


interface CacheInterface
{
	//初始化
	public static function getInstance();

	//获取cache
	public static function getCache();

	//设置缓存
	public static function set($key , $data , $time);

	//获取缓存
	public static function get($key);

	//获取多个缓存
	public static function getMultiple(array $keys);

	//删除缓存
	public static function remove($key);


}


