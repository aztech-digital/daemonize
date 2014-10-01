<?php

// Required in your entrypoint
declare(ticks = 1);

use Aztech\Daemonize\CallbackDaemon;
use Aztech\Daemonize\Daemonizer;
use Psr\Log\AbstractLogger;

require_once __DIR__ . '/../vendor/autoload.php';

class EchoLogger extends AbstractLogger
{
    public function log($level, $message, array $context = array()) {
        echo $message . PHP_EOL;
    }
}

class Dummy
{
    private $var = '0';

    private $reloads = 0;

    function init()
    {
        echo 'Initializing process... ' . PHP_EOL;
    }

    function run()
    {
        while (true) {
            $read = [ STDIN ];
            $write = $except = [ ];

            while (stream_select($read, $write, $except, 0) > 0) {
                echo 'stdin >> ' . fgets($read[0]);
            }

            $this->var = date(DATE_RSS);

            echo $this->var . PHP_EOL;

            sleep(5);
        }
    }

    function cleanup()
    {
        echo 'Cleaning up whatever... [' . $this->var  . ']' . PHP_EOL;

        $this->var = '';

        echo 'Cleaned up after maself ! [' . $this->var  . ']' . PHP_EOL;
    }

    function stop()
    {
        echo 'Stopping... [' . $this->var . ']' . PHP_EOL;
    }

    function reload()
    {
        $this->reloads++;

        echo 'Reloaded configuration : ' . $this->reloads . PHP_EOL;
    }

    function resume()
    {
        echo 'Resuming... [' . $this->var . ']' . PHP_EOL;
    }
}

$dummy = new Dummy();
$run = [ $dummy, 'run' ];
$cleanup = [ $dummy, 'cleanup' ];
$init = [ $dummy, 'init' ];

$callbackd = (new CallbackDaemon($run))
    ->onInitialize([ $dummy, 'init' ])
    ->onCleanup([ $dummy, 'cleanup' ])
    ->onPause([ $dummy, 'stop' ])
    ->onResume([ $dummy, 'resume' ])
    ->onReload([ $dummy, 'reload']);

$daemonizer = new Daemonizer($callbackd);
$daemonizer->setLogger(new EchoLogger());
$daemonizer->run();
