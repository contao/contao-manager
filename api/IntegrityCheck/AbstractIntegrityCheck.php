<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Contao\ManagerApi\I18n\Translator;

abstract class AbstractIntegrityCheck implements IntegrityCheckInterface
{
    public function __construct(private readonly Translator $translator)
    {
    }

    /**
     * Translates a string from the "integrity" domain.
     */
    protected function trans(string $id, array $params = []): string
    {
        return $this->translator->trans('integrity.'.$id, $params);
    }
}
