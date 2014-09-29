<?php

require_once __DIR__ . '/../vendor/autoload.php';

declare(ticks = 1);

use Aztech\Daemonize\CallbackDaemon;
use Aztech\Daemonize\Daemonizer;

$run = function ()
{
    // Long (or forever) running work goes here
};

$cleanup = function ()
{
    // Cleaning up goes here
};

$daemonizer = new Daemonizer(new CallbackDaemon($run, $cleanup));
$daemonizer->run();
