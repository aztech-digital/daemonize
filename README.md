daemonize
=========

Small library to handle POSIX signals from your application.

It is a simple wrapper on top of pcntl_* functions, and requires a PHP version compiled with pcntl support, which pretty much excludes Windows builds IIRC.

All this stuff is meant to make it easy to :

- Get the opportunity to do some cleanup work when a user quits your shell application with Ctrl + C.
- Pause/resume your application
- Restart your application (restarts the PHP interpreter under the same PID, so it's a restart with a new stack blablabla...)
- Send USR signals to your application

## Installation

### Via Composer

Composer is the only supported way of installing *aztech/daemonize* . Don't know Composer yet ? [Read more about it](https://getcomposer.org/doc/00-intro.md).

`$ composer require "aztech/daemonize":"~0.1.0"`

## Autoloading

Add the following code to your bootstrap file :

```
require_once 'vendor/autoload.php';
```

## Usage

```
<?php

require_once __DIR__ . '/../vendor/autoload.php';

declare(ticks = 1);

use Aztech\Daemonize\CallbackDaemon;
use Aztech\Daemonize\Daemonizer;

$run = function ()
{
    // Long (or forever) running work goes here
    while (true) {
        echo '.';
        sleep(1);
    }
};

$cleanup = function ()
{
    echo 'Ooops, I need to cleanup before I die...' . PHP_EOL;
    // Cleaning up goes here
};

// You can pass any object implementing \Aztech\Daemonize\Daemon here instead of a CallbackDaemon instance.
$callbackd = (new CallbackDaemon($run))
    ->onInitialize(function() { /* Invoked before main routine starts... */ })
    ->onCleanup(function() { /* Invoked on program shutdown (except when trigger by SIGKILL)... */ })
    ->onPause(function() { /* Invoked when user presses Ctrl+Z or sends SIGTSTP... */ }
    ->onResume(function() { 
        /* Invoked when program resumes after being paused via keyboard, SIGTSTP, or SIGSTOP */
        /* Warning : resume can not rely on pause being invoked, a process can be stopped via SIGSTOP, and cannot process it */
    })
    ->onKill(SIGUSR1, function($signal) {
        echo 'I got a USR signal : ' . $signal . PHP_EOL;
        /* Invoked when USR1 or USR2 signals are raised */
        /* First param to onKill is either SIGUSR1 or SIGUSR2 */
    });

$daemonizer = new Daemonizer($callbackd);
$daemonizer->run();
```

Once your process is running, you can now play with it and signals from your command line :

```bash
# Suspend program execution
kill -SIGTSTP `pgrep php`
# Resume program (in background mode)
kill -SIGCONT `pgrep php` 
# Bring program back to foreground
fg 

# Press Ctrl+Z to pause & detach, then "kill -SIGCONT" to resume execution in background

# Pausing without invoking pause handler
kill -SIGSTOP `pgrep php`
# Resume (resume handler is always invoked)
kill -SIGCONT `pgrep php`

# Restart the PHP interpreter and running script while keeping same PID.
kill -SIGHUP `pgrep php`

```

