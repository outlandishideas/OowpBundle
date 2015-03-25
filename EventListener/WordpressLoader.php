<?php

namespace Outlandish\OowpBundle\EventListener;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Loads Wordpress by requiring the wp-load.php file from the web folder
 *
 * Class WordpressLoader
 * @package Outlandish\OowpBundle\EventListener
 */
class WordpressLoader
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Requires the wp-load.php file
     *
     * Expects the wp-load.php file to be in the web/ folder of the project.
     * It gets this location using the kernel's getRootDir() method
     */
    public function loadWordpress()
    {
        $wpLoad = $this->kernel->getRootDir() . "/../web/wp-load.php";
        include $wpLoad;
    }
}