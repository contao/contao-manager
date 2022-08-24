<?php

declare(strict_types=1);

namespace Contao\ManagerApi\TaskOperation;

interface SponsoredOperationInterface
{
    public function getSponsor(): ?array;
}
