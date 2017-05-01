<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractIntegrityCheck implements IntegrityCheckInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Translates a string from the "integrity" domain.
     *
     * @param string $id
     * @param array  $parameters
     *
     * @return string
     */
    protected function trans($id, array $parameters = [])
    {
        return $this->translator->trans('integrity.'.$id, $parameters, 'integrity');
    }
}
