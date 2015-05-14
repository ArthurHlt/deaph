<?php
namespace ArthurH\Deaph;

use Aws\S3\S3Client;
use Dropbox\Client;
use League\Flysystem\Adapter\AwsS3;
use League\Flysystem\Adapter\Dropbox;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\Sftp;
use League\Flysystem\Adapter\Zip;
use League\Flysystem\Filesystem;

/**
 *
 */
class AdapterFactory
{
    private $arrayConfig;
    private $instance;
    private $proxy;

    function __construct($type, $arrayConfig)
    {
        $this->proxy = (!empty($_ENV['http_proxy']) ? $_ENV['http_proxy'] : null);
        $this->proxy = (!empty($_ENV['HTTP_PROXY']) ? $_ENV['http_proxy'] : null);
        $this->arrayConfig = $arrayConfig;
        $type = strtolower($type);
        switch ($type) {
            case 'sftp':
                $this->instanciateSftp();
                break;
            case 'ftp':
                $this->instanciateFtp();
                break;
            case 'zip':
                $this->instanciateZip();
                break;
            case 's3':
                $this->instanciateS3();
                break;
            case 'dropbox':
                $this->instanciateDropbox();
                break;
            default:
                $this->instanciateLocal();
                break;
        }
    }

    private function instanciateSftp()
    {
        $this->instance = new Sftp($this->arrayConfig);
    }

    private function instanciateS3()
    {
        $arrayClient = array(
            'key' => $this->arrayConfig['key'],
            'secret' => $this->arrayConfig['secret']
        );
        if (!empty($this->proxy)) {
            $arrayClient['request.options'] = array('proxy' => $this->proxy);
        }
        $client = S3Client::factory($arrayClient);
        $this->instance = new AwsS3($client, $this->arrayConfig['bucket-name']);
    }

    private function instanciateDropbox()
    {

        $client = new Client($this->arrayConfig['token'], $this->arrayConfig['appname']);
        $this->instance = new Dropbox($client);

    }

    private function instanciateFtp()
    {
        $this->instance = new Ftp($this->arrayConfig);
    }

    private function instanciateLocal()
    {
        $this->instance = new Local($this->arrayConfig['root']);
    }

    private function instanciateZip()
    {
        $this->instance = new Zip($this->arrayConfig['root'] . '/deploy.zip');
    }

    public function getInstance()
    {

        return $this->instance;
    }
}