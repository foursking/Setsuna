<?php

use Setsuna\Application;

require __DIR__.'/vendor/Autoloader.php';


$app = new \Setsuna\Core\Application(__DIR__ . '/app');

$app->run();


