<?php

namespace Aztech\Daemonize;

interface KillableDaemon extends DisposableDaemon
{

    function initialize();

    function kill($signal);

}
