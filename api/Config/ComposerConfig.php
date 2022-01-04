<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Filesystem\Filesystem;

class ComposerConfig extends AbstractConfig
{
    public function __construct(ApiKernel $kernel, Filesystem $filesystem = null)
    {
        parent::__construct('config.json', $kernel, $filesystem);

        // Make sure the config is in the correct subkey
        if (!$this->has('config')) {
            $this->replace(['config' => $this->all()]);
        }
    }

    public function config(): PartialConfig
    {
        return new PartialConfig($this, 'config');
    }

    public function repositories(): PartialConfig
    {
        return new PartialConfig($this, 'repositories');
    }

    public function allowContaoPlugins(): void
    {
        $config = $this->config();

        if ($config->has('allow-plugins')) {
            return;
        }

        $config->add([
            'allow-plugins' => [
                'composer/package-versions-deprecated' => true,
                'contao-community-alliance/composer-plugin' => true,
                'contao-components/installer' => true,
                'contao/manager-plugin' => true,
            ],
        ]);
    }
}
