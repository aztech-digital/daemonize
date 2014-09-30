<?php

namespace Aztech\Daemonize;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Daemonizer implements LoggerAwareInterface
{

    private $daemon;

    private $logger;

    private $signalHandler = null;

    private $waitForSigCont = false;

    public function __construct(Daemon $daemon)
    {
        $this->daemon = $daemon;
        $this->logger = new NullLogger();
        $this->signalHandler = new SignalHandler();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->signalHandler->setLogger($logger);
    }

    public function run()
    {
        $this->registerSignals();

        if ($this->daemon instanceof KillableDaemon) {
            $this->daemon->initialize();
        }

        try {
            $this->daemon->run();
        }
        catch (RestartException $ex) {
            return $this->replaceProcess();
        }
    }

    private function registerSignals()
    {
        $subs = [
            [ SIGHUP, [ $this, 'restartProcess' ] ],
            [ SIGINT, [ $this, 'killProcess' ] ],
            [ SIGTERM, [ $this, 'killProcess' ] ]
        ];

        if ($this->daemon instanceof KillableDaemon) {
            $subs[] = [ SIGUSR1, $this->getUsrSigCallback(SIGUSR1) ];
            $subs[] = [ SIGUSR2, $this->getUsrSigCallback(SIGUSR2) ];
        }

        if ($this->daemon instanceof ResumableDaemon) {
            $subs[] = [ SIGTSTP, [ $this, 'pauseProcess' ] ];
            $subs[] = [ SIGCONT, [ $this, 'resumeProcess' ] ];
        }

        foreach ($subs as $subscription) {
            $this->signalHandler->register($subscription[0], $subscription[1]);
        }
    }

    private function getUsrSigCallback($signal)
    {
        $daemon = $this->daemon;
        $callback = function() { };

        if ($daemon instanceof KillableDaemon) {
            $callback = function() use ($signal, $daemon) {
                return $daemon->kill($signal);
            };
        }

        return $callback;
    }

    public function restartProcess()
    {
        if (! ($this->daemon instanceof ReloadableDaemon)) {
            echo PHP_EOL . 'Gracefully restarting process...' . PHP_EOL . PHP_EOL;

            throw new RestartException('restart');
        }

        echo PHP_EOL . 'Gracefully reloading...' . PHP_EOL . PHP_EOL;

        $this->reloadProcess();
    }

    private function replaceProcess()
    {
        if (! CurrentProcess::getInstance()->restartInProc()) {
            throw new \RuntimeException('Unable to restart process');
        }
    }

    private function reloadProcess()
    {
        $this->daemon->reload();
    }


    public function killProcess()
    {
        echo PHP_EOL . 'Exiting program, waiting for graceful shutdown...' . PHP_EOL . PHP_EOL;

        if ($this->daemon instanceof KillableDaemon) {
            $this->daemon->cleanup();
        }

        exit();
    }

    public function pauseProcess()
    {
        if ($this->daemon instanceof ResumableDaemon) {
            $this->daemon->pause();
        }

        // Unregister then re-raise SIGTSTP to use default PHP signal handler
        $this->signalHandler->unregister(SIGTSTP);
        $this->signalHandler->processOnly(SIGTSTP);

        CurrentProcess::getInstance()->kill(SIGTSTP);
    }

    public function resumeProcess()
    {
        echo PHP_EOL . 'Resuming process...' . PHP_EOL;

        CurrentProcess::getInstance()->refresh();

        // Restore SIGTSTP custom handler
        $this->signalHandler->processAll();
        $this->signalHandler->register(SIGTSTP, [ $this, 'pauseProcess' ]);

        if ($this->daemon instanceof ResumableDaemon) {
            $this->daemon->resume();
        }
    }
}
