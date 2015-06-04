<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require($composerAutoload);

$loader->addPsr4('Database\\', "src/");

date_default_timezone_set('UTC');