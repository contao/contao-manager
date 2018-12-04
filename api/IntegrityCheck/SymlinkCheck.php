<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;

class SymlinkCheck extends AbstractIntegrityCheck
{
    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * Constructor.
     */
    public function __construct(ServerInfo $serverInfo, Translator $translator)
    {
        parent::__construct($translator);

        $this->serverInfo = $serverInfo;
    }

    public function run()
    {
        // Skip symlink check on Windows for now
        if ($this->serverInfo->getPlatform() === ServerInfo::PLATFORM_WINDOWS) {
            return null;
        }

        if ($this->canCreateSymlinks()) {
            return null;
        }

        return new ApiProblem(
            $this->trans('symlink.title'),
            'https://php.net/symlink'
        );
    }

    private function canCreateSymlinks()
    {
        if (!function_exists('symlink')) {
            return false;
        }

        $tmpfile = tempnam(sys_get_temp_dir(), '');

        @unlink($tmpfile);
        $result = @symlink(__FILE__, $tmpfile);
        @unlink($tmpfile);

        return true === $result;
    }
}
