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
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/contao-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/contao-manager
 * @filesource
 */

namespace AppBundle\Test;

use Tenside\Core\Util\HomePathDeterminator;

/**
 * This class provides information about the home path to use throughout the test run.
 */
class TestHomePathDeterminator extends HomePathDeterminator
{
    /**
     * The home path in use in the running test.
     *
     * @var string
     */
    private static $testHomePath;

    /**
     * Retrieve the home directory.
     *
     * @return string
     *
     * @throws \LogicException When the path has not been set from the test.
     */
    public function homeDir()
    {
        if (!isset(self::$testHomePath)) {
            throw new \LogicException('Home path not set for test environment, ensure the test sets it!');
        }

        return self::$testHomePath;
    }

    /**
     * Set the home path.
     *
     * @param string $homePath The home path to use.
     *
     * @return void
     */
    public static function setHomePath($homePath)
    {
        self::$testHomePath = $homePath;
    }

    /**
     * Reset the home path back to null.
     *
     * This is to be called from tearDown().
     *
     * @return void
     */
    public static function resetPath()
    {
        self::$testHomePath = null;
    }
}
