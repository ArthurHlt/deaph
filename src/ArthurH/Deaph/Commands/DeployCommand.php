<?php

namespace ArthurH\Deaph\Commands;

use Arhframe\IocArt\IocArt;
use ArthurH\Deaph\DeployerApi;
use ConsoleKit\Command;
use ConsoleKit\Widgets\Box;
use ConsoleKit\Widgets\ProgressBar;

/**
 * Deploy your app by using the .deaph.yml file
 */
class DeployCommand extends Command
{
    private $progress;
    private $nbFile;

    public function execute(array $args, array $options = array())
    {
        $box = new Box($this->console, 'Deployment with Deaph');
        $box->write();

        $ioc = new IocArt(__DIR__ . '/../context/context.yml');
        $ioc->loadContext();
        $deploy = $ioc->getBean('ArthurH.deployApi');
        $deploy->setDeployFileName(getcwd() . '/' . DeployerApi::$configFilename);
        if ($options['v']) {
            $deploy->setVerbose(true);
        }
        $deploy->setFolder(getcwd());
        $deploy->setObserverLoading($this);
        $deploy->deploy();
    }

    public function notify($nbFile)
    {
        if (empty($this->nbFile)) {
            $this->nbFile = $nbFile;
        }
        if (empty($this->progress)) {
            $this->progress = new ProgressBar($this->console, $this->nbFile, $this->nbFile);
            $this->progress->setShowRemainingTime(false);
        }
        $this->progress->incr();
        if ($this->progress->getValue() >= $this->nbFile) {
            $this->progress->stop();
        }
    }
} 