<?php

namespace Aztech\Daemonize;

class CallbackDaemon implements KillableDaemon, ResumableDaemon, ReloadableDaemon
{

    private $cleanupCallback = null;

    private $initCallback = null;

    private $killCallbacks = [];

    private $pauseCallback = null;

    private $reloadCallback = null;

    private $resumeCallback = null;

    private $runCallback = null;

    public function __construct(callable $runnable)
    {
        $this->runCallback = $runnable;
    }

    public function onCleanup(callable $runnable)
    {
        $this->cleanupCallback = $runnable;

        return $this;
    }

    public function onInitialize(callable $runnable)
    {
        $this->initCallback = $runnable;

        return $this;
    }

    public function onKill($usrSignal, callable $function)
    {
        if ($usrSignal !== SIGUSR1 && $usrSignal !== SIGUSR2) {
            throw new \InvalidArgumentException();
        }

        $this->killCallbacks[$usrSignal] = $function;

        return $this;
    }

    public function onPause(callable $runnable)
    {
        $this->pauseCallback = $runnable;

        return $this;
    }

    public function onReload(callable $runnable)
    {
        $this->reloadCallback = $runnable;

        return $this;
    }

    public function onResume(callable $runnable)
    {
        $this->resumeCallback = $runnable;

        return $this;
    }

    public function cleanup()
    {
        if ($this->cleanupCallback) {
            call_user_func($this->cleanupCallback);
        }
    }

    public function initialize()
    {
        if ($this->initCallback) {
            call_user_func($this->initCallback);
        }
    }

    public function kill($signal)
    {
        if (array_key_exists($signal, $this->killCallbacks) && $this->killCallbacks[$signal]) {
            call_user_func($this->killCallbacks[$signal]);
        }
    }

    public function pause()
    {
        if ($this->pauseCallback) {
            call_user_func($this->pauseCallback);
        }
    }

    public function reload()
    {
        if ($this->reloadCallback) {
            call_user_func($this->reloadCallback);
        }
        else {
            echo 'No reload function available, restarting instead...' . PHP_EOL;
            throw new RestartException();
        }
    }

    public function resume()
    {
        if ($this->resumeCallback) {
            call_user_func($this->resumeCallback);
        }
    }

    public function run()
    {
        if ($this->runCallback) {
            call_user_func($this->runCallback);
        }
    }
}
