<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\System;

class IpInfo
{
    /**
     * Resolves IP information of the current server.
     *
     * @return array
     */
    public function collect()
    {
        $template = [
            'ip' => '',
            'hostname' => '',
            'city' => '',
            'region' => '',
            'country' => '',
            'loc' => '',
            'org' => '',
        ];

        $data = @file_get_contents('https://ipinfo.io/json') ?: @file_get_contents('http://ipinfo.io/json');

        if (!empty($data)) {
            $template = array_merge($template, json_decode($data, true));
        }

        if (empty($template['ip'])) {
            $template['ip'] = (string) @file_get_contents('https://api.ipify.org');
        }

        if (empty($template['ip'])) {
            $template['ip'] = @file_get_contents('http://api.ipify.org');
        }

        if (empty($template['hostname'])) {
            $template['hostname'] = (string) @gethostbyaddr($template['ip']);
        }

        return $template;
    }
}
