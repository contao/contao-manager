<?php

/**
 * This file is part of contao/package-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/package-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/package-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/package-manager
 * @filesource
 */

namespace AppBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * WebTestCase is the base class for functional tests.
 */
abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * Temporary working dir.
     *
     * @var string
     */
    private static $workspace;

    /**
     * Flag if the working directory cleanup shall occur after each test or at AfterClass.
     *
     * @var bool
     */
    private static $shareWorkspace = false;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (!self::$shareWorkspace && (null !== self::$workspace)) {
            $filesystem = new Filesystem();
            $filesystem->remove(self::$workspace);
            self::$workspace = null;
            TestHomePathDeterminator::resetPath();
        }

        parent::tearDown();
    }

    /**
     * {@inheritDoc}
     */
    public static function tearDownAfterClass()
    {
        if (self::$shareWorkspace && (null !== self::$workspace)) {
            $filesystem = new Filesystem();
            $filesystem->remove(self::$workspace);
            self::$workspace = null;
            TestHomePathDeterminator::resetPath();
        }

        parent::tearDownAfterClass();
    }

    /**
     * Set the flag that the tests share the workspace.
     *
     * @return void
     */
    protected static function setWorkspaceShared()
    {
        self::$shareWorkspace = true;
    }

    /**
     * Retrieve the path to the fixtures directory.
     *
     * @return string
     */
    protected static function getFixturesDirectory()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';
    }

    /**
     * Create and return the path to a temp dir.
     *
     * @param string $subDirectory     The sub directory to create.
     *
     * @param bool   $forceDirectories Optional flag if the parenting dirs should be created.
     *
     * @return string
     */
    protected static function getTempDir($subDirectory = '', $forceDirectories = true)
    {
        if (null === self::$workspace) {
            self::$workspace = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('tenside-core-test');
            mkdir(self::$workspace, 0777, true);

            TestHomePathDeterminator::setHomePath(self::$workspace);
        }
        $temp = self::$workspace . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subDirectory);

        if (!is_dir($temp) && $forceDirectories) {
            mkdir($temp, 0777, true);
        }

        return $temp;
    }

    /**
     * Retrieve the path of a temp file within the temp dir of the test.
     *
     * @param string $name             Optional name of the file.
     *
     * @param bool   $forceDirectories Optional flag if the parenting dirs should be created.
     *
     * @return string
     */
    protected static function getTempFile($name = '', $forceDirectories = true)
    {
        if ('' === $name) {
            $name = uniqid();
        }

        return static::getTempDir(dirname($name), $forceDirectories) . DIRECTORY_SEPARATOR . basename($name);
    }

    /**
     * Provide a fixture in the temp directory and return the complete path to the new file or directory.
     *
     * @param string $path    The file name of the fixture.
     *
     * @param string $newPath The new path for the fixture.
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the source file does not exist.
     */
    protected static function provideFixture($path, $newPath = '')
    {
        if ('' === $newPath) {
            $newPath = $path;
        }

        $source     = static::getFixturesDirectory() . DIRECTORY_SEPARATOR . $path;
        $fullPath   = static::getTempFile($newPath);
        $filesystem = new Filesystem();

        if (!file_exists($source)) {
            throw new \InvalidArgumentException('Invalid fixture path given: ' . $path);
        }

        $filesystem->copy($source, $fullPath);

        return $fullPath;
    }

    /**
     * Provide a fixture directory in the temp directory and return the complete path to the new directory.
     *
     * @param string $path    The directory name of the fixture directory.
     *
     * @param string $newPath The new path for the fixture files (relative to temp root).
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the source directory does not exist.
     */
    protected static function provideFixtureDirectory($path, $newPath = '')
    {
        $source  = static::getFixturesDirectory() . DIRECTORY_SEPARATOR . $path;
        $destDir = $newPath ? $newPath . DIRECTORY_SEPARATOR : '';
        $parent  = basename($path) . DIRECTORY_SEPARATOR;

        if (!is_dir($source)) {
            throw new \InvalidArgumentException('Invalid fixture path given: ' . $path);
        }

        foreach (Finder::create()->in($source)->ignoreDotFiles(false)->ignoreVCS(false) as $file) {
            if (!is_dir($file)) {
                $relative = $file->getRelativePathName();
                static::provideFixture($parent . $relative, $destDir . $relative);
            }
        }

        return $destDir;
    }

    /**
     * Provide a fixture in the temp directory with the passed data and return the complete path to the new file.
     *
     * @param string $path    The file name of the fixture.
     *
     * @param string $content The fixture content.
     *
     * @return string
     */
    protected static function createFixture($path, $content)
    {
        file_put_contents($fullPath = static::getTempFile($path), $content);

        return $fullPath;
    }

    /**
     * Read the content of a fixture to memory and return it.
     *
     * @param string $path The fixture to read.
     *
     * @return string
     */
    protected static function readFixture($path)
    {
        return file_get_contents(static::getFixturesDirectory() . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * Ensure the contents of a zip file are present in the given dir.
     *
     * @param string $zipFile        The source zip to scan (full path).
     *
     * @param string $destinationDir The directory where the contents shall be checked (relative to temp dir).
     *
     * @return void
     */
    protected function assertZipHasBeenUnpackedTo($zipFile, $destinationDir = '')
    {
        $destinationDir = static::getTempDir($destinationDir, false);

        $zip = new \ZipArchive();
        $zip->open($zipFile);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat      = $zip->statIndex($i);
            $fileName  = $stat['name'];
            $localFile = $destinationDir . DIRECTORY_SEPARATOR . $fileName;
            $this->assertTrue(is_link($localFile) || file_exists($localFile), 'File does not exist ' . $localFile);

            if (is_link($destinationDir . DIRECTORY_SEPARATOR . $fileName)
                || is_dir($destinationDir . DIRECTORY_SEPARATOR . $fileName)) {
                continue;
            }
            $this->assertEquals(
                sprintf('%u', $stat['crc']),
                hexdec(hash_file('crc32b', $destinationDir . DIRECTORY_SEPARATOR . $fileName)),
                'CRC mismatch for ' . $fileName
            );
        }
    }
}
