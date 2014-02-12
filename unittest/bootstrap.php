<?php
$autoloaderScript = __DIR__ . '/../vendor/autoload.php';
// $autoloaderScript = 'C:\e43\workspace\utLib\vendor\autoload.php';

$loader = require $autoloaderScript;
$loader->add('pimpleFw', dirname(__FILE__).'/../src');

