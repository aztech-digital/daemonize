<?php

namespace Aztech\Daemonize;

final class CurrentProcess
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private $pid = 0;

    private $uid = 0;

    private $threadId = 0;

    private $entryPoint = null;

    private $arguments = null;

    private $userTime;

    private function __construct()
    {
        $this->inspect();
    }

    private function inspect()
    {
        $this->inspectProcess();
        $this->inspectEntrypoint();
    }

    private function inspectProcess()
    {
        $this->pid = getmypid();
        $this->uid = getmyuid();

        if (function_exists('zend_thread_id')) {
            $this->threadId = zend_thread_id();
        }

        $data = getrusage(0);

        $this->userTime = $data["ru_utime.tv_usec"];
    }

    private function inspectEntrypoint()
    {
        global $argv;

        $scriptName = $_SERVER['SCRIPT_FILENAME'];
        $arguments = array_slice($argv, 1);

        if ($this->shouldNormalizeEntrypoint($scriptName, $arguments)) {
            $this->normalizeEntrypoint($scriptName, $arguments);
        }

        $this->entryPoint = $scriptName;
        $this->arguments = $arguments;
    }

    private function shouldNormalizeEntrypoint($scriptName, array $arguments) {
        return (! $this->hasShebang($scriptName) || ! is_executable($scriptName));
    }

    private function normalizeEntrypoint(& $scriptName, array & $arguments)
    {
        if (! empty($arguments)) {
            array_unshift($arguments, '--');
        }

        array_unshift($arguments, $scriptName);

        $scriptName = PHP_BINARY;
    }

    /**
     * May not be necessary. Checks whether given file starts with a shebang
     *
     * @param string $file
     * @return boolean
     */
    private function hasShebang($file)
    {
        $contents = explode(PHP_EOL, file_get_contents($file), 2);


        return (strpos($contents[0], '#!') === 0);
    }

    /**
     * Restart the current script by replacing its process with a new invocation of the current script.
     *
     * @desc <br /><em>Warning</em>
     * @desc Calling this method will restart and reload the current script, including source files. Any change applied to
     * configuration
     */
    public function restartInProc()
    {
        return pcntl_exec($this->entryPoint, $this->arguments);
    }

    public function kill($signal)
    {
        return posix_kill($this->pid, $signal);
    }

    public function rename($name)
    {
        return cli_set_process_title($name);
    }

}
