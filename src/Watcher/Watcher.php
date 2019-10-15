<?php declare(strict_types=1);

namespace seregazhuk\PhpWatcher\Watcher;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use seregazhuk\PhpWatcher\Filesystem\ChangesListener;
use seregazhuk\PhpWatcher\Screen\Screen;

final class Watcher
{
    private $loop;

    private $screen;

    private $filesystemListener;

    public function __construct(LoopInterface $loop, Screen $screen, ChangesListener $filesystemListener)
    {
        $this->loop = $loop;
        $this->screen = $screen;
        $this->filesystemListener = $filesystemListener;
    }

    public function startWatching(Process $process, float $delayToRestart): void
    {
        $this->screen->start($process->getCommand());
        $this->screen->showSpinner($this->loop);
        $this->startProcess($process);

        $this->filesystemListener->start();
        $this->filesystemListener->on('change', function () use ($process, $delayToRestart) {
            $this->restartProcess($process, $delayToRestart);
        });

        $this->loop->run();
    }

    private function startProcess(Process $process): void
    {
        $process->start($this->loop);
        $this->screen->subscribeToProcessOutput($process);
    }

    private function restartProcess(Process $process, float $delayToRestart): void
    {
        $process->terminate();
        $this->screen->restarting($process->getCommand());

        $this->loop->addTimer($delayToRestart, function () use ($process) {
            $this->startProcess($process);
        });
    }
}
