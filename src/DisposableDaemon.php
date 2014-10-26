<?php

namespace Aztech\Daemonize;

interface DisposableDaemon extends Daemon
{

    function cleanup();

}
