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

use Tenside\Core\Config\TensideJsonConfig;
use Tenside\Core\Task\AbstractTaskFactory;
use Tenside\Core\Util\HomePathDeterminator;
use Tenside\Core\Util\JsonArray;

/**
 * This class is the factory for all app bundle tasks.
 */
class AppTaskFactory extends AbstractTaskFactory
{
    /**
     * The home path.
     *
     * @var HomePathDeterminator
     */
    private $home;

    /**
     * The configuration in use.
     *
     * @var TensideJsonConfig
     */
    private $config;

    /**
     * Create a new instance.
     *
     * @param HomePathDeterminator $home   The home path to use.
     *
     * @param TensideJsonConfig    $config The configuration in use.
     */
    public function __construct(HomePathDeterminator $home, TensideJsonConfig $config)
    {
        $this->home   = $home;
        $this->config = $config;
    }

    /**
     * Create a cache clear task instance.
     *
     * @param JsonArray $metaData The meta data for the task.
     *
     * @return ContaoCacheClearTask
     */
    protected function createContaoCacheClear($metaData)
    {
        if (!$metaData->has(ContaoCacheClearTask::SETTING_HOME)) {
            $metaData->set(ContaoCacheClearTask::SETTING_HOME, $this->home->homeDir());
        }
        if (!$metaData->has(ContaoCacheClearTask::SETTING_PHP)) {
            $metaData->set(ContaoCacheClearTask::SETTING_PHP, $this->config->getPhpCliBinary());
        }

        return new ContaoCacheClearTask($metaData);
    }
}
