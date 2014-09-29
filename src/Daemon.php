<?php

namespace Aztech\Daemonize;

interface Daemon
{

    function setup();

    function run();

    function cleanup();
}
