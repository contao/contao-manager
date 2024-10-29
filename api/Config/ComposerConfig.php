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
    public function __construct(ApiKernel $kernel, Filesystem $filesystem)
    {
        parent::__construct(
            $kernel->getConfigDir().\DIRECTORY_SEPARATOR.'config.json',
            $filesystem,
            $kernel->getTranslator(),
        );
    }

    public function config(): PartialConfig
    {
        return new PartialConfig($this, 'config');
    }

    public function repositories(): PartialConfig
    {
        return new PartialConfig($this, 'repositories');
    }

    public function allowPlugins(): void
    {
        $config = $this->config();

        if (true !== $config->get('allow-plugins')) {
            $config->set('allow-plugins', true);
        }
    }

    protected function initialize(): void
    {
        if (null !== $this->data) {
            return;
        }

        parent::initialize();

        // Make sure the config is in the correct subkey
        if (!$this->has('config') || [] === $this->get('config')) {
            $config = $this->all();
            unset($config['config']);
            $this->replace(['config' => $config]);
        }
    }
}
