<?php

namespace Aztech\Daemonize;

interface ResumableDaemon extends Daemon
{

    function pause();

    function resume();
}
