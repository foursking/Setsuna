<?php

require __DIR__.'/Setsuna/Bootstrap.php';

$app = new \Setsuna\Core\Application();
$container = $app->init($container);

$app->run($container);

