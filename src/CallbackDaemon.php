<?php

namespace Aztech\Daemonize;

class CallbackDaemon implements Daemon
{

    private $run;

    private $cleanup;

    public function __construct(callable $run, callable $cleanup)
    {
        $this->run = $run;
        $this->cleanup = $cleanup;
    }

    public function setup()
    {
        return;
    }

    public function run()
    {
        call_user_func($this->run);
    }

    public function cleanup()
    {
        call_user_func($this->cleanup);
    }
}
