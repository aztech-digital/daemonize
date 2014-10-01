<?php

namespace Aztech\Daemonize;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Signal handling class. So this is an event dispatcher, basically.
 *
 * @author thibaud
 */
class SignalHandler implements LoggerAwareInterface
{

    /**
     * Map of registered handlers indexed by signal.
     *
     * @var callable[]
     */
    private $handlers = [ ];

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Main signal handler that will dispatch signal to registered handlers.
     *
     * @var callable
     */
    private $signalHandler;

    /**
     * Map of allowable signals indexed by value
     *
     * @var mixed[]
     */
    private $signals = [
        SIGCONT => 'SIGCONT',
        SIGHUP  => 'SIGHUP',
        SIGINT  => 'SIGINT',
        SIGTERM => 'SIGTERM',
        SIGTSTP => 'SIGTSTP',
        SIGUSR1 => 'SIGUSR1',
        SIGUSR2 => 'SIGUSR2'
    ];

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->signalHandler = [ $this, 'handleSignal' ];
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function validateSignal($signal)
    {
        if (! array_key_exists($signal, $this->signals)) {
            throw new \InvalidArgumentException("Unsupported signal number '$signal'.");
        }
    }

    public function clear()
    {
        foreach ($this->signals as $signal => $callbacks) {
            pcntl_signal($signal, SIG_DFL);
        }
    }

    public function register($signal, callable $callback)
    {
        $this->validateSignal($signal);

        if (! array_key_exists($signal, $this->handlers)) {
            $this->handlers[$signal] = [];

            pcntl_signal($signal, $this->signalHandler, true);
        }

        $this->handlers[$signal][] = $callback;
    }

    public function unregister($signal)
    {
        $this->validateSignal($signal);

        if (array_key_exists($signal, $this->handlers)) {
            unset($this->handlers[$signal]);
        }

        pcntl_signal($signal, SIG_DFL);
    }

    public function handleSignal($signal)
    {
        $signalName = $this->signals[$signal];

        if ($signal == SIGINT || $signal == SIGTSTP) {
            echo "\033[D\033[D\033[2K" . PHP_EOL;
        }

        if (! array_key_exists($signal, $this->handlers)) {
            $this->logger->debug("Ignoring '$signalName', no registered handlers.");
        }

        $this->logger->debug("Dispatching '$signalName' to registered handlers.");

        foreach ($this->handlers[$signal] as $handler) {
            call_user_func($handler, $signal);
        }
    }

    public function processOnly($signal)
    {
        $signals = array_keys($this->signals);
        $signals = array_diff($signals, [ $signal ]);

        pcntl_sigprocmask(SIG_SETMASK, $signals);
    }

    public function processAll()
    {
        pcntl_sigprocmask(SIG_UNBLOCK, array_keys($this->signals));
    }
}
