<?php

declare(strict_types=1);

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
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

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

        $data = $this->request->get('https://ipinfo.io/json', $status, true) ?: $this->request->get('http://ipinfo.io/json', $status, true);

        if (!empty($data)) {
            $template = array_merge($template, json_decode($data, true));
        }

        if (empty($template['ip'])) {
            $template['ip'] = $this->request->get('https://api.ipify.org', $status, true);
        }

        if (empty($template['ip'])) {
            $template['ip'] = $this->request->get('http://api.ipify.org', $status, true);
        }

        if (empty($template['hostname'])) {
            $template['hostname'] = (string) @gethostbyaddr($template['ip']);
        }

        return $template;
    }
}
