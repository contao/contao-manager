<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Tests\Composer;

use Contao\ManagerApi\Composer\CloudJob;
use PHPUnit\Framework\TestCase;

class CloudJobTest extends TestCase
{
    /**
     * @dataProvider waitingTime
     */
    public function testCalculatesTheWaitingTime(int $queuePosition, int $avgTime, int $workers, int $expected): void
    {
        $job = new CloudJob([
            'status' => CloudJob::STATUS_QUEUED,
            'queuePosition' => $queuePosition,
            'stats' => [
                'averageProcessingTimeInMs' => $avgTime * 1000,
                'numberOfJobsInQueue' => $queuePosition,
                'numberOfWorkers' => $workers,
            ],
        ]);

        $this->assertSame($expected, $job->getWaitingTime());
    }

    public static function waitingTime(): iterable
    {
        yield [12, 30, 6, 60];

        yield [10, 10, 5, 20];

        yield [3, 25, 8, 9];
    }
}
