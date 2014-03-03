<?php


require_once __DIR__.'/Core/Loader.php';
require_once __DIR__.'/Core/Pimple.php';


if(file_exists(dirname(__DIR__) .'/vendor/autoload.php')){
	require_once dirname(__DIR__) .'/vendor/autoload.php';
}






$container = new \Setsuna\Core\Pimple();
$container['APP_ROOT'] = dirname(__DIR__). '/app/';


$container['github'] = $container->share(function(){

	return new \Github\Client();

});


\Setsuna\Core\Loader::autoload(true, dirname(__DIR__));
