<?php
namespace ArthurH\Deaph\steps;

use ArthurH\Deaph\SshFactory;
use ArthurH\Deaph\Utils;
use League\Flysystem\Filesystem;
use Symfony\Component\Yaml\Yaml;


/**
 *
 */
class SshStep extends AbstractStep
{
    private $ssh;

    public function execute()
    {
        if (empty($this->step['commands'])) {
            throw new \Exception("Error in deploy step " . $this->step['number'] . " no commands have been set", 1);
        }
        if (empty($this->ssh)) {
            try {
                $deployConfig = $this->getDeployConfig();
                if ($deployConfig['filesystem']['type'] == 'sftp') {
                    $sshConfig = $deployConfig['filesystem'];
                } else {
                    $sshConfig = $deployConfig['ssh'];
                }
                $factorySsh = new SshFactory($sshConfig);
                $this->ssh = $factorySsh->getSsh();
            } catch (\Exception $e) {
                throw new \Exception("Error when creating connection to server ssh: " . $e->getMessage());
            }
        }
        if (!is_array($this->step['commands'])) {
            $this->step['commands'] = array($this->step['commands']);
        }
        foreach ($this->step['commands'] as $command) {
            Utils::echoer("Run command: '" . $command . "'. \n");
            $result = $this->ssh->exec($command);
            Utils::echoer("Result command:\n$result\n");
        }
    }
}