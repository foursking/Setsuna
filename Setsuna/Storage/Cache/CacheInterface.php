<?php

namespace Setsuna\Storage\Cache;


interface CacheInterface
{
	//初始化
	public  function getInstance();

	//获取cache
	public  function getCache();

	//设置缓存
	public  function set($key , $data , $time);

	//获取缓存
	public  function get($key);

	//获取多个缓存
	public  function getMultiple(array $keys);

	//删除缓存
	public  function remove($key);


}


