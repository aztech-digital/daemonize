<?php

namespace Aztech\Daemonize;

interface KillableDaemon extends Daemon
{

    function initialize();

    function cleanup();

    function kill($signal);

}
