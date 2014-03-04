<?php


require_once __DIR__.'/Core/Loader.php';
require_once __DIR__.'/Core/Pimple.php';


$venderFile = dirname(__DIR__) .'/vendor/autoload.php';


if(file_exists($venderFile)) require_once $venderFile;




$container = new \Setsuna\Core\Pimple();
$container['APP_ROOT'] = dirname(__DIR__). '/app/';

\Setsuna\Core\Loader::autoload(true, dirname(__DIR__));
