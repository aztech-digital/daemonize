daemonize
=========

Small library to give a long running tasks an opportunity to perform some cleanup routines  when it is not cleanly exited (ie. via interrupts / kill).

It is a simple wrapper on top of pcntl_* functions, and requires a PHP version compiled with pcntl support, which pretty much excludes Windows builds IIRC.

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
};

$cleanup = function ()
{
    // Cleaning up goes here
};

// You can pass any object implementing \Aztech\Daemonize\Daemon here instead of a CallbackDaemon instance.
$daemonizer = new Daemonizer(new CallbackDaemon($run, $cleanup));
$daemonizer->run();
```
