<?php

use Composer\Autoload\ClassLoader;

$loader = new ClassLoader();
$loader->addPsr4('WebtreesCompactTheme\\', __DIR__);

$loader->register();

