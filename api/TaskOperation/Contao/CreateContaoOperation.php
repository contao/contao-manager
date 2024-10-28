<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Contao;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;

class CreateContaoOperation extends AbstractInlineOperation
{
    private static array $supportedVersions = ['4.9', '4.13', '5.3', '5.4'];

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $publicDir;

    public function __construct(
        TaskConfig $taskConfig,
        private readonly Environment $environment,
        ApiKernel $kernel,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct($taskConfig);
        $this->version = $taskConfig->getOption('version');

        if (!\in_array($this->version, static::$supportedVersions, true)) {
            throw new \InvalidArgumentException('Unsupported Contao version');
        }

        $this->publicDir = $taskConfig->getState('public-dir');

        if (null !== $this->publicDir) {
            return;
        }

        if ($kernel->getProjectDir() === $kernel->getPublicDir()) {
            throw new \RuntimeException('Cannot install without a public directory.');
        }

        $taskConfig->setState('public-dir', $this->publicDir = $kernel->getPublicDir());
    }

    public function getSummary(): string
    {
        return 'composer create-project contao/managed-edition:'.$this->version;
    }

    protected function getName(): string
    {
        return 'create-project';
    }

    protected function doRun(): bool
    {
        $protected = [
            $this->environment->getJsonFile(),
            $this->environment->getLockFile(),
            $this->environment->getVendorDir(),
        ];

        if ($this->filesystem->exists($protected)) {
            throw new \RuntimeException('Cannot install into existing application');
        }

        $this->filesystem->dumpFile(
            $this->environment->getJsonFile(),
            $this->generateComposerJson(
                $this->taskConfig->getOption('version'),
                (bool) $this->taskConfig->getOption('core-only', false),
            ),
        );

        return true;
    }

    private function generateComposerJson(string $version, bool $coreOnly = false): string
    {
        $coreBundle = '';

        if ($this->isDevVersion($version)) {
            $version .= '.x-dev';
            $coreBundle = ',
        "contao/core-bundle": "'.$version.'"';
        } else {
            $version .= '.*';
        }

        if ($coreOnly) {
            $require = <<<JSON
                        "contao/conflicts": "*@dev",
                        "contao/manager-bundle": "{$version}"{$coreBundle}
                JSON;
        } else {
            $require = <<<JSON
                        "contao/conflicts": "*@dev",
                        "contao/manager-bundle": "{$version}"{$coreBundle},
                        "contao/calendar-bundle": "{$version}",
                        "contao/comments-bundle": "{$version}",
                        "contao/faq-bundle": "{$version}",
                        "contao/listing-bundle": "{$version}",
                        "contao/news-bundle": "{$version}",
                        "contao/newsletter-bundle": "{$version}"
                JSON;
        }

        // https://github.com/contao/contao-manager/issues/627 Still needed since we
        // allow Contao 4.9 for PHP < 7.4
        if (version_compare($version, '4.12', '>=')) {
            $publicDir = basename($this->publicDir);
            $script = '@php vendor/bin/contao-setup';
        } else {
            $publicDir = 'web';
            $script = 'Contao\\\\ManagerBundle\\\\Composer\\\\ScriptHandler::initializeApplication';
        }

        return <<<JSON
            {
                "type": "project",
                "require": {
            {$require}
                },
                "extra": {
                    "public-dir": "{$publicDir}",
                    "contao-component-dir": "assets"
                },
                "scripts": {
                    "post-install-cmd": [
                        "{$script}"
                    ],
                    "post-update-cmd": [
                        "{$script}"
                    ]
                }
            }
            JSON;
    }

    private function isDevVersion(string $version): bool
    {
        return false;
    }
}
