<?php

namespace Aztech\Daemonize;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Daemonizer implements LoggerAwareInterface
{

    private $daemon;

    private $logger;

    private $signals = [
        SIGTERM => 'SIGTERM',
        SIGINT => 'SIGINT',
        SIGHUP => 'SIGHUP',
        SIGTSTP => 'SIGTSTP'
    ];

    private $waiting = false;

    public function __construct(Daemon $daemon)
    {
        $this->daemon = $daemon;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        $run = $this->attachSignals();

        while ($run) {
            try {
                $run = false;

                $this->daemon->setup();
                $this->daemon->run();
            } catch (RestartException $ex) {
                $run = true;
            }

            $this->daemon->cleanup();
        }
    }

    private function attachSignals()
    {
        $sigHandler = [
            $this,
            "handleSignal"
        ];

        foreach ($this->signals as $signal) {
            $this->logger->debug("Attaching signal handler for $signal");

            if (! pcntl_signal(constant($signal), $sigHandler, false)) {
                $this->logger->error("Failed to attach handler for $signal.");

                return false;
            }
        }

        return true;
    }

    public function handleSignal($signal)
    {
        $signalName = $this->signals[$signal];
        $this->logger->debug("Caught signal $signalName");

        switch ($signal) {
            case SIGHUP:
                $this->restartProcess();
                break;
            case SIGKILL:
            case SIGINT:
            case SIGTERM:
            case SIGTSTP:
                $this->killProcess();
                break;
        }

        $this->logger->debug("Ignored received signal");
    }

    private function restartProcess()
    {
        echo PHP_EOL . 'Gracefully restarting process...' . PHP_EOL . PHP_EOL;

        throw new RestartException('restart');
    }

    private function killProcess()
    {
        echo PHP_EOL . 'Exiting program, waiting for graceful shutdown...' . PHP_EOL . PHP_EOL;

        $this->waiting = true;
        $this->daemon->cleanup();
        $this->waiting = false;

        exit();
    }
}
