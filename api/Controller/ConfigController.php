<?php

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Tenside\HomePathDeterminator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;

class ConfigController extends Controller
{
    /**
     * @var HomePathDeterminator
     */
    private $home;
    /**
     * @var null|Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param HomePathDeterminator $home
     * @param Filesystem|null      $filesystem
     */
    public function __construct(HomePathDeterminator $home, Filesystem $filesystem = null)
    {
        $this->home = $home;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function __invoke()
    {
        // If the action is unsuccessful, the Tenside controller will throw an exception
        $response = $this->forward('TensideCoreBundle:InstallProject:configure');
        $htaccess = $this->home->homeDir() . '/.htaccess';

        if (!file_exists($htaccess)) {
            $this->filesystem->dumpFile(
                $htaccess,
                <<<'TAG'
# This file must be present to prevent Composer from creating it
# see https://github.com/contao/contao-manager/issues/58
TAG

            );
        }

        return $response;
    }
}
