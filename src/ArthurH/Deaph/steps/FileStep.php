<?php
namespace ArthurH\Deaph\steps;

use League\Flysystem\Filesystem;
use Symfony\Component\Yaml\Yaml;


/**
 *
 */
class FileStep extends AbstractStep
{

    public function execute()
    {
        $this->getFilesystem()->put($this->step['file'], $this->step['value']);
    }
}