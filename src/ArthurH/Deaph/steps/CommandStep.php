<?php
namespace ArthurH\Deaph\steps;

use ArthurH\Deaph\DeployerApi;
use ArthurH\Deaph\Utils;
use League\Flysystem\Filesystem;
use Symfony\Component\Yaml\Yaml;


/**
 *
 */
class CommandStep extends AbstractStep
{

    public function execute()
    {
        if (empty($this->step['commands'])) {
            throw new \Exception("Error in deploy step " . $this->step['number'] . " no commands have been set", 1);
        }
        if (!is_array($this->step['commands'])) {
            $this->step['commands'] = array($this->step['commands']);
        }
        foreach ($this->step['commands'] as $command) {
            Utils::echoer("Run command: '" . $command . "'. \n");
            $result = shell_exec($command);
            Utils::echoer("Result command:\n$result\n");
        }
    }
}