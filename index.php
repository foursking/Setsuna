<?php

use Setsuna\Application;

require __DIR__.'/vendor/framework/Autoloader.php';


$app = new Application(__DIR__ . '/app/');

$app->run();

