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

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Controller\Server\ContaoController;
use Contao\ManagerApi\Exception\ProcessOutputException;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoApi;
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\System\ServerInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ContaoControllerTest extends TestCase
{
    private ContaoConsole&MockObject $console;

    private ContaoApi&MockObject $api;

    private ServerInfo&MockObject $serverInfo;

    private Filesystem&MockObject $filesystem;

    private ContaoController $controller;

    protected function setUp(): void
    {
        $kernel = $this->createConfiguredMock(ApiKernel::class, [
            'getProjectDir' => '/project',
            'getPublicDir' => '/project/public',
        ]);
        $factory = $this->createConfiguredMock(ConsoleProcessFactory::class, [
            'getContaoConsolePath' => '/project/contao-console',
        ]);
        $this->console = $this->createMock(ContaoConsole::class);
        $this->api = $this->createMock(ContaoApi::class);
        $this->serverInfo = $this->createMock(ServerInfo::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->controller = new ContaoController($kernel, $this->api, $this->console, $factory, new NullLogger(), $this->filesystem);
    }

    /**
     * @dataProvider requestedProperties
     */
    public function testFetchesOnlySelectedData(array|string|null $properties, array $expected, string $method = 'GET'): void
    {
        $this->configureInstallation();
        $needsVersion = null === $properties || [] !== array_intersect(array_keys($expected), ['version', 'supported', 'cli', 'api', 'config', 'conflicts']);
        $consoleCalls = [
            'getVersion' => [$needsVersion, '5.3.52'],
            'getCommandList' => [isset($expected['cli']), []],
            'getConfig' => [isset($expected['config']), $expected['config'] ?? []],
        ];

        foreach ($consoleCalls as $methodName => [$called, $value]) {
            $this->console
                ->expects($this->exactly((int) $called))
                ->method($methodName)
                ->willReturn($value)
            ;
        }

        foreach (['getVersion' => 2, 'getFeatures' => [], 'getCommands' => []] as $methodName => $value) {
            $this->api
                ->expects($this->exactly((int) isset($expected['api'])))
                ->method($methodName)
                ->willReturn($value)
            ;
        }

        $query = null === $properties ? '' : '?'.http_build_query(['properties' => $properties]);
        $request = Request::create('/api/server/contao'.$query, $method);
        $response = 'POST' === $method
            ? $this->controller->update($request, $this->serverInfo)
            : $this->controller->handle($request, $this->serverInfo);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected, json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public static function requestedProperties(): iterable
    {
        $full = [
            'version' => '5.3.52',
            'cli' => ['commands' => []],
            'api' => ['version' => 2, 'features' => [], 'commands' => []],
            'config' => ['backend' => ['badge_title' => 'Example']],
            'supported' => true,
            'conflicts' => [],
            'project_dir' => '/project',
            'public_dir' => 'public',
            'directory_separator' => \DIRECTORY_SEPARATOR,
        ];

        yield 'default response' => [null, $full];
        yield 'POST ignores selector' => ['unknown', $full, 'POST'];
        yield 'POST ignores array selector' => [['version', 'supported'], $full, 'POST'];
        yield 'POST ignores directory selector' => ['project_dir', $full, 'POST'];
        yield 'all properties' => [implode(',', array_keys($full)), $full];
        yield 'monitoring' => ['version,supported', ['version' => '5.3.52', 'supported' => true]];
        yield 'whitespace and duplicates' => [' supported, version, version ', ['version' => '5.3.52', 'supported' => true]];

        foreach ($full as $property => $value) {
            yield $property => [$property, [$property => $value]];
        }
    }

    /**
     * @dataProvider invalidProperties
     */
    public function testRejectsInvalidPropertiesBeforeStartingProcesses(mixed $properties): void
    {
        $this->configureInstallation();

        $this->console
            ->expects($this->exactly((int) str_contains((string) $properties, 'version')))
            ->method('getVersion')
        ;

        $this->console
            ->expects($this->never())
            ->method('getCommandList')
        ;

        $this->api
            ->expects($this->never())
            ->method('getVersion')
        ;

        $this->api
            ->expects($this->never())
            ->method('getFeatures')
        ;

        $this->api
            ->expects($this->never())
            ->method('getCommands')
        ;

        $response = $this->controller->handle(Request::create('/api/server/contao', 'GET', ['properties' => $properties]), $this->serverInfo);

        $this->assertSame(400, $response->getStatusCode());
    }

    public static function invalidProperties(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => [' '];
        yield 'unknown' => ['version,unknown'];
        yield 'nested property' => ['config.backend'];
        yield 'empty item' => ['version,'];
        yield 'array' => [['version', 'supported']];
    }

    public function testMissingPhpKeepsErrorResponse(): void
    {
        $this->configureInstallation(phpExecutable: null);

        $this->console
            ->expects($this->never())
            ->method('getVersion')
        ;
        $response = $this->controller->handle(Request::create('/api/server/contao?properties=version,supported'), $this->serverInfo);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertArrayHasKey('title', json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @dataProvider versionFailures
     */
    public function testVersionFailureKeepsFullErrorResponse(bool $invalidOutput, string $method = 'GET'): void
    {
        $this->configureInstallation();
        $process = $this->createConfiguredMock(Process::class, [
            'getExitCode' => 1,
            'getErrorOutput' => 'Console failed',
        ]);
        $exception = $invalidOutput ? new ProcessOutputException('Invalid output', $process) : new ProcessFailedException($process);
        $this->console
            ->method('getVersion')
            ->willThrowException($exception)
        ;

        $this->console
            ->expects($this->never())
            ->method('getConfig')
        ;

        $this->api
            ->expects($this->never())
            ->method('getVersion')
        ;

        $request = Request::create('/api/server/contao?properties=version,supported', $method);

        if ('POST' === $method) {
            $response = $this->controller->update($request, $this->serverInfo);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame('', $response->getContent());

            return;
        }

        $response = $this->controller->handle($request, $this->serverInfo);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(502, $response->getStatusCode());
        $this->assertFalse($data['supported']);
        $this->assertNotEmpty($data['error']);
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('project_dir', $data);
    }

    public static function versionFailures(): iterable
    {
        yield 'invalid output' => [true];
        yield 'process failed' => [false];
        yield 'POST invalid output' => [true, 'POST'];
        yield 'POST process failed' => [false, 'POST'];
    }

    public function testMissingContaoReturnsSelectedDefaultsWithoutScanningProject(): void
    {
        $this->configureInstallation(installed: false);

        $this->console
            ->expects($this->never())
            ->method('getVersion')
        ;

        $this->console
            ->expects($this->never())
            ->method('getConfig')
        ;

        $this->api
            ->expects($this->never())
            ->method('getVersion')
        ;

        $response = $this->controller->handle(Request::create('/api/server/contao?properties=version,supported'), $this->serverInfo);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['version' => null, 'supported' => false], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function configureInstallation(bool $installed = true, string|null $phpExecutable = '/usr/bin/php'): void
    {
        $this->serverInfo
            ->method('getPhpExecutable')
            ->willReturn($phpExecutable)
        ;

        $this->filesystem
            ->method('exists')
            ->willReturn($installed)
        ;
    }
}
