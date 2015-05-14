<?php

namespace ArthurH\Deaph;

use Arhframe\Util\File;
use Arhframe\Util\Folder;
use Arhframe\Yamlarh\Yamlarh;
use ArthurH\Deaph\steps\AbstractStep;
use League\Flysystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use ConsoleKit\Widgets\ProgressBar;
use ConsoleKit\Widgets\Box;

ignore_user_abort(true);
set_time_limit(0);

/**
 *
 */
class DeployerApi
{
    private $filesToUpload = null;
    private $deployConfig;
    private $filesystem;
    private $startTime;
    private $steppers;
    private $observerLoading;
    private $deployFileName;
    private $folder;
    private $nbFile;
    private $verbose = false;
    public static $configFilename = '.deaph.yml';
    public static $ignoreFilename = '.deaphignore.yml';

    public function __construct()
    {

    }

    public function deployBegin()
    {
        $this->startTime = microtime(true);
        $this->echoerShow("\nDeployment start at " . date('H:i:s') . ".\n");
        $this->echoerShow("Get all files to deploy.\n");
        $folder = new Folder($this->folder);
        $yamlarh = new Yamlarh($this->deployFileName);
        $this->deployConfig = $yamlarh->parse();
        $files = $folder->getFiles('/.*/', true);
        foreach ($files as $file) {
            $this->filesToUpload[] = $file->absolute();
        }
        $this->echoerShow("Filtering files with ignore list.\n");
        $this->removeIgnoredFile();

        if (trim(strtolower($this->deployConfig['filesystem']['type'])) == 'sftp') {
            if (empty($this->deployConfig['filesystem']['port'])) {
                $this->deployConfig['filesystem']['port'] = 22;
            }
        }
        $factory = new AdapterFactory($this->deployConfig['filesystem']['type'], $this->deployConfig['filesystem']);
        $this->filesystem = new Filesystem($factory->getInstance());
        foreach ($this->filesToUpload as $key => $value) {
            $value = str_replace($this->folder, '', $value);
            $this->filesToUpload[$key] = $value;
        }

        $this->echoerShow("Files can be uploaded.\n");
        $this->echoerShow("===========================================================\n");
    }

    private function removeIgnoredFile()
    {
        $folder = new Folder(__DIR__ . '/../../');
        $this->deployConfig['ignore'][] = 'deaph.phar';
        foreach ($this->deployConfig['ignore'] as $ignore) {
            $ignore = trim($ignore);
            if ($ignore[0] == '/') {
                $ignore = $this->folder . $ignore;
            } else {
                $ignore = $this->folder . '/' . $ignore;
            }
            $this->removeFromFolder(Utils::rglob($ignore));
        }
    }

    private function removeFromFolder($files)
    {
        if (empty($files)) {
            return;
        }
        $toUnset = null;
        foreach ($files as $file) {
            foreach ($this->filesToUpload as $key => $fileUpload) {
                if ($fileUpload == $file) {
                    if ($this->verbose) {
                        Utils::echoer(" - Ignore: " . $file . "\n");
                    }
                    $toUnset[] = $key;
                    break;
                }
            }
        }
        if (empty($toUnset)) {
            return;
        }

        foreach ($toUnset as $value) {
            unset($this->filesToUpload[$value]);
        }
    }

    public function doSteps()
    {
        if (!is_array($this->deployConfig['steps'])) {
            return;
        }
        $i = 1;
        foreach ($this->deployConfig['steps'] as $step) {
            if ($i > 1) {
                $this->echoerShow("------\n");
            }
            if (!empty($step['skip'])) {
                $this->echoerShow("Step $i skipped.\n");
                $i++;
                continue;
            }
            $this->echoerShow("Start step $i.\n");
            $step['type'] = strtolower($step['type']);
            $step['number'] = $i;
            if (!empty($this->steppers[$step['type']])
                && $this->steppers[$step['type']] instanceof AbstractStep
            ) {
                $this->steppers[$step['type']]->setStep($step);
                $this->steppers[$step['type']]->execute();
            }

            $this->echoerShow("Step $i finished.\n\n");

            $i++;
        }
        $this->echoerShow("===========================================================\n");
    }


    public function deploy()
    {
        $this->deployBegin();
        $confFilesystem = $this->deployConfig['filesystem'];
        if (!empty($this->deployConfig['filesystem']['password'])) {
            unset($confFilesystem['password']);
        }

        $this->echoerShow("Start deployment with file system: \n" . Yaml::dump($confFilesystem) . "\n");
        $i = 1;
        $this->nbFile = count($this->filesToUpload);
        $this->echoerShow("Uploading files... \n");
        foreach ($this->filesToUpload as $key => $file) {
            $this->observerLoading->notify($this->nbFile);
            if ($this->isModified($file)) {
                $localFile = new File($this->folder . $file);
                if ($this->verbose) {
                    Utils::echoer(" + Send: " . $file->absolute() . "\n");
                }
                $this->filesystem->put($file, $localFile->getContent());
            }
            $i++;
        }
        $this->echoerShow("Upload in file system finished.\n");
        $this->echoerShow("===========================================================\n");
        $this->doSteps();
        $this->echoerShow("Deployment finished in " . Utils::formatDuration(microtime(true) - $this->startTime) . " at " . date('H:i:s') . ".\n");

    }

    private function echoerShow($string)
    {
        echo $string;
        flush();
    }


    private function isModified($file)
    {
        $localFile = new File($this->folder . $file);
        return $localFile->getSize() != $this->filesystem->getSize($file);
    }

    /**
     * @param mixed $filesystem
     */
    public function setFilesystem($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return mixed
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @param mixed $deployConfig
     */
    public function setDeployConfig($deployConfig)
    {
        $this->deployConfig = $deployConfig;
    }

    /**
     * @return mixed
     */
    public function getDeployConfig()
    {
        return $this->deployConfig;
    }

    /**
     * @Required
     */
    public function setSteppers(array $steppers)
    {
        $this->steppers = $steppers;
        foreach ($steppers as $stepper) {
            if ($stepper instanceof AbstractStep) {
                $stepper->setDeployerApi($this);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getSteppers()
    {
        return $this->steppers;
    }

    /**
     * @param mixed $observerLoading
     */
    public function setObserverLoading($observerLoading)
    {
        $this->observerLoading = $observerLoading;
    }

    /**
     * @return mixed
     */
    public function getObserverLoading()
    {
        return $this->observerLoading;
    }

    /**
     * @return mixed
     */
    public function getDeployFileName()
    {
        return $this->deployFileName;
    }

    /**
     * @param mixed $deployFileName
     *
     */
    public function setDeployFileName($deployFileName)
    {
        $this->deployFileName = $deployFileName;
    }

    /**
     * @return mixed
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param mixed $folder
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
    }

    public function addStepper(AbstractStep $stepper)
    {
        if (in_array($stepper, $this->steppers)) {
            return;
        }
        $stepper->setDeployerApi($this);
        $this->steppers[] = $stepper;
    }

    /**
     * @return boolean
     */
    public function getVerbose()
    {
        return $this->verbose;
    }

    /**
     * @param boolean $verbose
     */
    public function setVerbose($verbose)
    {
        $this->verbose = $verbose;
    }

}
