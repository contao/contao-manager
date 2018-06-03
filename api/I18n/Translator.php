<?php

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
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $labels = [];

    /**
     * Constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Gets label for given ID from translation files.
     *
     * @param string $id
     * @param array  $params
     *
     * @return string
     */
    public function trans($id, array $params = [])
    {
        $locales = ['en'];

        if (null !== ($request = $this->requestStack->getCurrentRequest())) {
            $locale = $request->getLocale();

            if (5 === strlen($locale)) {
                array_unshift($locales, substr($locale, 0, 2));
            }

            array_unshift($locales, $locale);
        }

        return $this->replaceParameters($this->findLabel($id, $locales), $params);
    }

    /**
     * Searches for label by ID in the given locales.
     *
     * @param string $id
     * @param array  $locales
     *
     * @return string
     */
    private function findLabel($id, array $locales)
    {
        foreach ($locales as $locale) {
            $this->load($locale);

            if (isset($this->labels[$locale][$id])) {
                return $this->labels[$locale][$id];
            }
        }

        return $id;
    }

    /**
     * Replaces parameters in label.
     *
     * @param string $label
     * @param array  $params
     *
     * @return string
     */
    private function replaceParameters($label, array $params)
    {
        if (empty($params)) {
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
     *
     * @param string $locale
     */
    private function load($locale)
    {
        if (isset($this->labels[$locale])) {
            return;
        }

        $file = __DIR__.'/../Resources/i18n/'.$locale.'.yml';

        if (!is_file($file)) {
            return;
        }

        $data = Yaml::parse(file_get_contents($file));

        if (empty($data[$locale]) || !is_array($data[$locale])) {
            return;
        }

        $this->store($locale, $data[$locale]);
    }

    /**
     * Adds new labels to the label store by flattening array keys.
     *
     * @param string $locale
     * @param array  $data
     * @param string $prefix
     */
    private function store($locale, array $data, $prefix = '')
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $this->store($locale, $v, $prefix.$k.'.');
            } else {
                $this->labels[$locale][$prefix.$k] = $v;
            }
        }
    }
}
