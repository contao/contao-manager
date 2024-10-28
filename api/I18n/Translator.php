<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\I18n;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

class Translator
{
    private array $labels = [];

    /**
     * Constructor.
     */
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * Gets label for given ID from translation files.
     */
    public function trans(string $id, array $params = []): string
    {
        $locales = ['en'];

        if (null !== ($request = $this->requestStack->getCurrentRequest())) {
            $locale = $request->getLocale();

            if (5 === \strlen($locale)) {
                array_unshift($locales, substr($locale, 0, 2));
            }

            array_unshift($locales, $locale);
        }

        return $this->replaceParameters($this->findLabel($id, $locales), $params);
    }

    /**
     * Searches for label by ID in the given locales.
     */
    private function findLabel(string $id, array $locales): string
    {
        foreach ($locales as $locale) {
            $this->load($locale);

            if (isset($this->labels[$locale][$id]) && ('' !== $this->labels[$locale][$id] || 'en' === $locale)) {
                return $this->labels[$locale][$id];
            }
        }

        return $id;
    }

    /**
     * Replaces parameters in label.
     */
    private function replaceParameters(string $label, array $params): string
    {
        if ($params === []) {
            return $label;
        }

        $replace = [];

        foreach ($params as $k => $v) {
            $replace['{'.$k.'}'] = $v;
        }

        return strtr($label, $replace);
    }

    /**
     * Loads labels from file for given locale if it exists.
     */
    private function load(string $locale): void
    {
        if (isset($this->labels[$locale])) {
            return;
        }

        $file = __DIR__.'/../Resources/i18n/'.$locale.'.yml';

        if (!is_file($file)) {
            return;
        }

        $data = Yaml::parse(file_get_contents($file));

        if (empty($data[$locale]) || !\is_array($data[$locale])) {
            return;
        }

        $this->store($locale, $data[$locale]);
    }

    /**
     * Adds new labels to the label store by flattening array keys.
     */
    private function store(string $locale, array $data, string $prefix = ''): void
    {
        foreach ($data as $k => $v) {
            if (\is_array($v)) {
                $this->store($locale, $v, $prefix.$k.'.');
            } else {
                $this->labels[$locale][$prefix.$k] = $v;
            }
        }
    }
}
