<?php

namespace Contao\ManagerApi\Tests\Composer;

use Contao\ManagerApi\Composer\CloudJob;
use PHPUnit\Framework\TestCase;

class CloudJobTest extends TestCase
{
    /**
     * @dataProvider waitingTime
     */
    public function testCalculatesTheWaitingTime(int $queuePosition, int $avgTime, int $workers, $expected)
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

    public function waitingTime()
    {
        yield [12, 30, 6, 60];

        yield [10, 10, 5, 20];

        yield [3, 25, 8, 9];
    }
}
