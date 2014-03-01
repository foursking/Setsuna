<?php

/** **/

require_once __DIR__.'/Core/Loader.php';

if(file_exists('../vendor/autoload.php'))
	require_once dirname(__DIR__) .'/vendor/autoload.php';

//require_once __DIR__.'/Setsuna/Core/Pimple.php';

//$container = new \Setsuna\Core\Pimple();
//$container['APP_ROOT'] = dirname(dirname(__DIR__). '/app/');


$container = new Pimple();

\Setsuna\Core\Loader::autoload(true, dirname(__DIR__));
