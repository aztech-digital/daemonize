<?php

namespace Aztech\Daemonize;

/**
 * Implementors should implement this interface to signal that a given daemon
 * can reload its configuration without restarting the current process.
 *
 * @author thibaud
 */
interface ReloadableDaemon extends Daemon
{
    function reload();
}
