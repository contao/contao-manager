<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Tests\Controller;

use Contao\ManagerApi\Controller\Server\IpInfoController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

class IpInfoControllerTest extends TestCase
{
    public function testSuccessfulApiRequest(): void
    {
        $client = new MockHttpClient([
            new MockResponse(['{"ip":"8.8.8.8","much-more-information":true}'], ['http_code' => 200]),
        ]);

        $controller = new IpInfoController($client);
        $response = $controller(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"ip":"8.8.8.8","much-more-information":true}', $response->getContent());
    }

    public function testUnsuccessfulApiRequest(): void
    {
        $client = new MockHttpClient([
            new MockResponse(['{"error": true}'], ['http_code' => 500]),
        ]);

        $controller = new IpInfoController($client);
        $response = $controller(new Request());

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame('{"error":"HTTP 500 returned for \u0022https:\/\/iplist.cc\/api\/\u0022."}', $response->getContent());
    }
}
