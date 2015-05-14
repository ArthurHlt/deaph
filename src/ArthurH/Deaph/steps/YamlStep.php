<?php
namespace ArthurH\Deaph\steps;

use Arhframe\Yamlarh\Yamlarh;
use ArthurH\Deaph\Utils;
use League\Flysystem\Filesystem;
use Symfony\Component\Yaml\Yaml;


/**
 *
 */
class YamlStep extends AbstractStep
{


    public function execute()
    {
        try {
            $yamlarh = new Yamlarh($this->step['file']);
            $value = $yamlarh->parse();
        } catch (\Exception $e) {
            throw new \Exception("Error in deploy step " . $this->step['number'] . " : " . $e->getMessage());
        }
        $value = Utils::array_merge_recursive_distinct($value, $this->step['value']);
        $value = Yaml::dump($value, 6);
        $this->getFilesystem()->put($yamlarh->getFilename(), $value);
    }


}