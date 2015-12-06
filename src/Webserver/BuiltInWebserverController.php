<?php

namespace Cjm\Behat\LocalWebserverExtension\Webserver;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Exception\RuntimeException;

final class BuiltInWebserverController implements WebserverController
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @var BasicConfiguration
     */
    private $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    public function startServer()
    {
        $this->startServerProcess();
        $this->waitForServerToSTart();
    }

    public function isStarted()
    {
        return $this->process->isStarted() && $this->portIsAcceptingConnections();
    }

    public function stopServer()
    {
        $this->process->stop();
    }

    /**
     * @return string
     */
    private function getCommand()
    {
        $phpFinder = new PhpExecutableFinder();

        $command = sprintf(
             'exec %s -S %s -t %s %s',
             $phpFinder->find(),
             $this->config->getHost() . ':' . $this->config->getPort(),
             $this->config->getDocroot(),
             $this->config->getRouter()
        );

        return escapeshellcmd(trim($command));
    }

    /**
     * @return resource
     */
    private function portIsAcceptingConnections()
    {
        return @fsockopen($this->config->getHost(), $this->config->getPort());
    }

    private function waitForServerToSTart()
    {
        $timeout = microtime(true) + 5;
        while (!$this->isStarted()) {
            if (microtime(true) < $timeout) {
                sleep(0.1);
                continue;
            }
            throw new \RuntimeException('Webserver did not start within 5 seconds: ' . $this->process->getErrorOutput());
        }
    }

    private function startServerProcess()
    {
        $command = $this->getCommand();

        $this->process = new Process($command);
        $this->process->start();
    }
}

