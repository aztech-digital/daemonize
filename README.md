daemonize
=========

Small library to handle POSIX signals from your application.

It is a simple wrapper on top of pcntl_* functions, and requires a PHP version compiled with pcntl support, which pretty much excludes Windows builds IIRC.

All this stuff is meant to make it easy to :

- Get the opportunity to do some cleanup work when a user quits your shell application with Ctrl + C, SIGINT, or SIGTERM.
- Perform cleanup routines when your application is stopped (not quit) via SIGTSTP.
- Perform restore routines when your application is resumed via SIGCONT.
- Restart your application (restarts the PHP interpreter under the same PID, so it's a restart with a new stack blablabla...) or reload your config when your application receives a SIGHUP.
- Respond to USR signals sent to your application

## Installation

### Via Composer

Composer is the only supported way of installing *aztech/daemonize* . Don't know Composer yet ? [Read more about it](https://getcomposer.org/doc/00-intro.md).

`$ composer require "aztech/daemonize":"~0.1.0"`

## Autoloading

Add the following code to your bootstrap file :

```php
require_once 'vendor/autoload.php';
```

## Usage

```php
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
    ->onInitialize(function() { 
        /* Invoked before main routine starts... */ 
        echo 'Initializing process...' . PHP_EOL;
    })
    ->onCleanup(function() { 
        /* Invoked on program shutdown (except when trigger by SIGKILL)... */ 
        echo 'Cleaning up...' . PHP_EOL;
    })
    ->onPause(function() { 
        /* Invoked when user presses Ctrl+Z or sends SIGTSTP... */ 
        echo 'Pausing execution...' . PHP_EOL;
    }
    ->onResume(function() { 
        /* Invoked when program resumes after being paused via keyboard, SIGTSTP, or SIGSTOP */
        /* Warning : resume can not rely on pause being invoked as a process can be stopped 
           via SIGSTOP, and when that happens, the signal is never transferred to the process. */
        echo 'Resuming execution...' . PHP_EOL;
    })
    ->onKill(SIGUSR1, function($signal) {
        /* Invoked when USR1 or USR2 signals are raised */
        /* First param to onKill is either SIGUSR1 or SIGUSR2 */
        echo 'I got a USR signal : ' . $signal . PHP_EOL;
    });

$daemonizer = new Daemonizer($callbackd);
$daemonizer->run();
```

Once your process is running, it can process signals that you send :

```bash
# Suspend program execution
kill -SIGTSTP `pgrep php`
# Resume program (in background mode)
kill -SIGCONT `pgrep php` 
# Bring program back to foreground
fg 

# Press Ctrl+Z to pause & detach, then "bg" to resume execution in background

# Pausing without invoking pause handler
kill -SIGSTOP `pgrep php`
# Resume (resume handler is always invoked)
kill -SIGCONT `pgrep php`

# Restart the PHP interpreter and running script while keeping same PID.
kill -SIGHUP `pgrep php`

```

