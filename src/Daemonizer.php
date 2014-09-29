<?php

namespace Aztech\Daemonize;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Daemonizer implements LoggerAwareInterface
{

    private $daemon;

    private $logger;

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
        if ($this->attachSignals()) {
            $this->daemon->setup();
            $this->daemon->run();
            $this->daemon->cleanup();
        }
    }

    private function attachSignals()
    {


        $sigHandler = [ $this, "handleSignal"];
        $signals = [ 'SIGTERM', 'SIGINT' ];

        foreach ($signals as $signal) {
            $this->logger->debug("Attaching signal handler for $signal");

            if (! pcntl_signal(constant($signal), $sigHandler)) {
                $this->logger->error("Failed to attach handler for $signal.");

                return false;
            }
        }

        return true;
    }

    public function handleSignal($signal)
    {
        $this->logger->debug("Caught signal $signal");

        switch ($signal) {
            case SIGTERM:
            case SIGKILL:
            case SIGINT:
                $this->killProcess();
        }

        $this->logger->debug("Ignored received signal");
    }

    private function killProcess()
    {
        $this->logger->debug('Invoking cleanup.');
        $this->daemon->cleanup();

        $this->logger->debug('Exit.');
        exit();
    }
}
