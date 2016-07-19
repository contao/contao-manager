<?php

/**
 * This file is part of contao/contao-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/contao-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/contao-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/contao-manager
 * @filesource
 */

namespace AppBundle\Task;

use Tenside\Core\Task\AbstractCliSpawningTask;
use Tenside\Core\Util\ProcessBuilder;

/**
 * This class runs the cache clear command.
 */
class ContaoCacheClearTask extends AbstractCliSpawningTask
{
    /**
     * The home path of tenside.
     */
    const SETTING_HOME = 'home';

    /**
     * The php binary.
     */
    const SETTING_PHP = 'php';

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException When either cache cleaning failed.
     */
    public function doPerform()
    {
        $this->runCacheClearCommand('dev');
        $this->runCacheClearCommand('prod');
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'contao-cache-clear';
    }

    /**
     * Run the cache clear command.
     *
     * @param string $environment The name of the environment to clear.
     *
     * @return void
     */
    private function runCacheClearCommand($environment)
    {
        $process = ProcessBuilder::create($this->file->get(self::SETTING_PHP))
            ->setArguments(['vendor/bin/contao-console', 'cache:clear', '--no-warmup', '--env=' . $environment])
            ->setWorkingDirectory($this->file->get(self::SETTING_HOME))
            ->generate();

        $this->runProcess($process);
    }
}
