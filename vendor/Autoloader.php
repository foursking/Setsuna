<?php

/** **/

require_once __DIR__.'/Setsuna/Core/Loader.php';
//require_once __DIR__.'/Setsuna/Core/Pimple.php';

//$container = new \Setsuna\Core\Pimple();
//$container['APP_ROOT'] = dirname(dirname(__DIR__). '/app/');
\Setsuna\Core\Loader::autoload(true, __DIR__);
