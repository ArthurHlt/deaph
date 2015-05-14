<?php
/**
 * Created by IntelliJ IDEA.
 * User: arthurhalet
 * Date: 05/08/14
 * Time: 03:38
 */

namespace ArthurH\Deaph\Commands;


use ConsoleKit\Colors;
use ConsoleKit\Command;

/**
 * Get deaph version
 */
class VersionCommand extends Command
{
    public function execute(array $args, array $options = array())
    {
        echo Colors::colorize('Deaph', Colors::GREEN) . ' version ' . Colors::colorize(file_get_contents(__DIR__ . '/../../../../version'), Colors::YELLOW) . "\n";
    }

} 