<?php
/**
 * Created by IntelliJ IDEA.
 * User: arthurhalet
 * Date: 05/08/14
 * Time: 14:15
 */

namespace ArthurH\Deaph\Commands;


use ArthurH\Deaph\DeployerApi;
use ConsoleKit\Command;
use ConsoleKit\Widgets\Dialog;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends Command
{
    private $arrayPrepared;
    private $dialog;

    public function execute(array $args, array $options = array())
    {
        $this->dialog = new Dialog($this->getConsole());

        $filesystem = $this->dialog->ask('Choose a filesystem for deployment (local|zip|s3|ftp|sftp|dropbox|): ');
        $filesystem = strtolower($filesystem);
        $configFs = null;
        switch ($filesystem) {
            case 'sftp':
                $configFs = $this->dialogSftp();
                break;
            case 'ftp':
                $configFs = $this->dialogFtp();
                break;
            case 'zip':
                $configFs = $this->dialogZip();
                break;
            case 's3':
                $configFs = $this->dialogS3();
                break;
            case 'dropbox':
                $configFs = $this->dialogDropbox();
                break;
            default:
                $configFs = $this->dialogLocal();
                break;
        }
        $this->arrayPrepared['filesystem'] = $configFs;

        if ($this->dialog->confirm('Would you use ssh to make remote commands?')) {
            $this->arrayPrepared['ssh'] = $this->dialogSsh();
        }
        $this->arrayPrepared['steps'] = array(array('type' => 'command', 'commands' => array('ls')));
        $this->arrayPrepared['@import'] = array(DeployerApi::$ignoreFilename);
        $this->getConsole()->writeln('Creating ' . DeployerApi::$configFilename . ' .');
        file_put_contents(getcwd() . '/' . DeployerApi::$configFilename, Yaml::dump($this->arrayPrepared));
        $this->getConsole()->writeln('Creating ' . DeployerApi::$ignoreFilename . ' .');
        $ignore = array('ignore' => $this->createIgnore());
        file_put_contents(getcwd() . '/' . DeployerApi::$ignoreFilename, Yaml::dump($ignore));
    }

    private function createIgnore()
    {
        $ignore = array();
        $ignore[] = DeployerApi::$configFilename;
        $ignore[] = DeployerApi::$ignoreFilename;
        return $ignore;
    }

    private function dialogFtp()
    {
        $config = array();
        $config['type'] = 'ftp';
        $host = $this->dialog->ask('Choose a host, must be uri or ip address: ');
        $config['host'] = $host;
        $username = $this->dialog->ask('Set username to access to server: ');
        $config['username'] = $username;
        $password = $this->dialog->ask('Set password to access to server : ');
        $config['password'] = $password;
        $port = $this->dialog->ask('Set the ftp port (21): ');
        if (empty($port)) {
            $port = 21;
        }
        $config['port'] = $port;
        $root = $this->dialog->ask('Set the root folder for you server (/): ');
        if (empty($root)) {
            $root = '/';
        }
        $config['root'] = $root;
        return $config;
    }

    private function dialogLocal()
    {
        $config = array();
        $config['type'] = 'local';
        $path = $this->dialog->ask('Choose a path to deploy on local (' . getcwd() . '): ');
        if (empty($path)) {
            $path = getcwd();
        }
        $config['root'] = $path;
        return $config;
    }

    private function dialogZip()
    {
        $config = array();
        $config['type'] = 'zip';
        $path = $this->dialog->ask('Choose a path to deploy inside a zip, zip will be called "deploy.zip" (' . getcwd() . '): ');
        if (empty($path)) {
            $path = getcwd();
        }
        $config['root'] = $path;
        return $config;
    }

    private function dialogDropbox()
    {
        $config = array();
        $config['type'] = 'dropbox';
        $token = null;
        while (empty($token)) {
            $token = $this->dialog->ask('Your Dropbox token (cannot be empty): ');
        }
        $config['token'] = $token;
        $appName = null;
        while (empty($appName)) {
            $appName = $this->dialog->ask('Your Dropbox app name (cannot be empty): ');
        }
        $config['appname'] = $appName;
        return $config;
    }

    private function dialogS3()
    {
        $config = array();
        $config['type'] = 's3';
        $key = null;
        while (empty($key)) {
            $key = $this->dialog->ask('Your S3 key (cannot be empty): ');
        }
        $config['key'] = $key;
        $secret = null;
        while (empty($secret)) {
            $secret = $this->dialog->ask('Your S3 secret (cannot be empty): ');
        }
        $config['secret'] = $secret;
        $bucketName = null;
        while (empty($bucketName)) {
            $bucketName = $this->dialog->ask('Your S3 bucket name (cannot be empty): ');
        }
        $config['bucket-name'] = $bucketName;
        return $config;
    }

    private function dialogSftp()
    {
        $config = array();
        $config['type'] = 'sftp';
        $host = $this->dialog->ask('Choose a host, must be uri or ip address: ');
        $config['host'] = $host;

        $username = $this->dialog->ask('Set username to access to server: ');
        $config['username'] = $username;
        $password = $this->dialog->ask('Set password to access to server : ');
        $config['password'] = $password;
        $port = $this->dialog->ask('Set the sftp port (22): ');
        if (empty($port)) {
            $port = 22;
        }
        $config['port'] = $port;

        $root = $this->dialog->ask('Set the root folder for you server (/): ');
        if (empty($root)) {
            $root = '/';
        }
        $config['root'] = $root;
        return $config;
    }

    private function dialogSsh()
    {
        $config = array();
        if ($this->arrayPrepared['filesystem']['type'] == 'sftp') {
            $this->getConsole()->writeln('We use sftp information for ssh');
            $config = $this->arrayPrepared['filesystem'];
            unset($config['type']);
            $config['privateKey'] = '';
            return $config;
        }


        $host = $this->dialog->ask('Choose a host, must be uri or ip address: ');
        $config['host'] = $host;
        if ($this->dialog->confirm('Do you want use a RSA key?', 'y', array('y', 'N'), 'n')) {
            $key = $this->dialog->ask('Path to your RSA key: ');
            $config['privateKey'] = $key;
        } else {
            $username = $this->dialog->ask('Set username to access to server: ');
            $config['username'] = $username;
            $password = $this->dialog->ask('Set password to access to server : ');
            $config['password'] = $password;
        }
        $port = $this->dialog->ask('Set the sftp port (22): ');
        if (empty($port)) {
            $port = 22;
        }
        $config['port'] = $port;
        $root = $this->dialog->ask('Set the root folder for you server (/): ');
        if (empty($root)) {
            $root = '/';
        }

        return $config;
    }
} 