<?php

/**
 * This file is part of tenside/ui.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/ui
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/ui
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Ui\Compiler;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use Tenside\Compiler\AbstractTask;

/**
 * This Compiler task adds all content from tenside/ui into the phar.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class UiTask extends AbstractTask
{
    /**
     * Run the passed process and add every line from the output to the log.
     *
     * @param Process $process The process to execute.
     *
     * @return void
     */
    private function runProcess(Process $process)
    {
        $process->setTimeout(null)->mustRun();
        foreach (explode(PHP_EOL, $process->getOutput()) as $line) {
            $this->notice($line);
        }
    }

    /**
     * Compile the tenside/ui assets.
     *
     * @param string $compiledAssets The destination path for the assets.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function compileUi($compiledAssets)
    {
        $uiDir = $this->getPackageRoot('tenside/ui');

        if (!is_dir($compiledAssets)) {
            mkdir($compiledAssets, 0755, true);
        }
        $compiledAssets = realpath($compiledAssets);
        $this->notice('Performing npm install...');
        $process = new Process('npm install --save-dev', $uiDir);
        $this->runProcess($process);

        $this->notice('Performing gulp install...');
        $process = new Process($uiDir . '/node_modules/.bin/gulp install', $uiDir);
        $this->runProcess($process);
        $this->notice('Performing gulp build...');
        $process = new Process(
            $uiDir . '/node_modules/.bin/gulp build',
            $uiDir,
            array_merge(
                $_SERVER,
                [
                    'DEST_DIR' => $compiledAssets,
                    'TENSIDE_VERSION' => $this->getVersionInformationFor('tenside/core', 'version'),
                    'COMPOSER_VERSION' => $this->getVersionInformationFor('composer/composer', 'version'),
                ]
            )
        );
        $this->runProcess($process);
    }

    /**
     * {@inheritDoc}
     *
     * Add tenside/ui to the phar file.
     */
    public function compile()
    {
        $compiledAssets = dirname(dirname(__DIR__)) . '/.build';

        $this->compileUi($compiledAssets);

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('*.yml')
            ->notName('UiTask.php')
            ->in(dirname(dirname(__DIR__)) . '/src');
        foreach ($finder as $file) {
            $this->addFile($file);
        }

        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->name('*.css')
            ->name('*.js')
            ->name('*.map')
            ->name('*.png')
            ->name('*.svg')
            ->name('*.html')
            ->name('*.otf')
            ->name('*.eot')
            ->name('*.ttf')
            ->name('*.woff')
            ->name('*.woff2')
            ->name('*.json')
            ->in($compiledAssets);
        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            $this->addFile($file, false, str_replace($compiledAssets, 'assets', $file->getRealPath()));
        }
    }
}
