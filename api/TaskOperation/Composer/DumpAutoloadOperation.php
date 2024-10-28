<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class DumpAutoloadOperation extends AbstractProcessOperation
{
    public function __construct(
        ConsoleProcessFactory $processFactory,
        private readonly Translator $translator,
    ) {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('dump-autoload'));
        } catch (\Exception) {
            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    [
                        'composer',
                        'dump-autoload',
                        '--optimize',
                    ],
                    'dump-autoload',
                ),
            );
        }
    }

    public function getSummary(): string
    {
        return 'composer dump-autoload';
    }

    public function getDetails(): string|null
    {
        $total = $this->getTotalClasses($this->process->getOutput());

        if (null !== $total) {
            return $this->translator->trans('taskoperation.dump-autoload.result', ['count' => $total]);
        }

        return '';
    }

    private function getTotalClasses(string $output): string|null
    {
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (preg_match('{Generated optimized autoload files containing ([\d.]+) classes}', $line, $match)) {
                return $match[1];
            }
        }

        return null;
    }
}
