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

use Contao\ManagerApi\Controller\LogController;
use PHPUnit\Framework\TestCase;

class LogPatternTest extends TestCase
{
    /**
     * @dataProvider logMessages
     */
    public function testLogPattern(string $line, string $datetime, string $channel, string $level, string $message, string $context, string $extra): void
    {
        $result = preg_match(LogController::MONOLOG_PATTERN, $line, $matches);

        $this->assertSame(1, $result);
        $this->assertSame($line, $matches[0]);
        $this->assertSame($datetime, $matches['datetime'], 'DateTime does not match');
        $this->assertSame($channel, $matches['channel'], 'Channel does not match');
        $this->assertSame($level, $matches['level'], 'Level does not match');
        $this->assertSame($message, $matches['message'], 'Message does not match');
        $this->assertSame($context, $matches['context'], 'Context does not match');
        $this->assertSame($extra, $matches['extra'], 'Extra does not match');
    }

    public function logMessages(): \Generator
    {
        yield [
            '[2024-08-20T00:41:04.411801+02:00] request.INFO: Matched route "tl_page.32". {"route":"tl_page.32","route_parameters":{"_controller":"Contao\\FrontendIndex::renderPage","_scope":"frontend","_locale":"de","_format":"html","_canonical_route":"tl_page.32","pageModel":{"Contao\\PageModel":[]},"_route":"tl_page.32","_route_object":{"Contao\\CoreBundle\\Routing\\Page\\PageRoute":[]}}} []',
            '2024-08-20T00:41:04.411801+02:00',
            'request',
            'INFO',
            'Matched route "tl_page.32".',
            '{"route":"tl_page.32","route_parameters":{"_controller":"Contao\\FrontendIndex::renderPage","_scope":"frontend","_locale":"de","_format":"html","_canonical_route":"tl_page.32","pageModel":{"Contao\\PageModel":[]},"_route":"tl_page.32","_route_object":{"Contao\\CoreBundle\\Routing\\Page\\PageRoute":[]}}}',
            '[]',
        ];

        yield [
            '[2024-08-20T00:00:05.476105+02:00] app.ERROR: Github\Exception\RuntimeException: Bad credentials in vendor/knplabs/github-api/lib/Github/HttpClient/Plugin/GithubExceptionThrower.php:137 Stack trace: #0 vendor/php-http/httplug/src/Promise/HttpFulfilledPromise.php(31): Github\HttpClient\Plugin\GithubExceptionThrower->Github\HttpClient\Plugin\{closure}(Object(Nyholm\Psr7\Response)) #1 vendor/knplabs/github-api/lib/Github/HttpClient/Plugin/GithubExceptionThrower.php(28): Http\Client\Promise\HttpFulfilledPromise->then(Object(Closure)) #2 vendor/php-http/client-common/src/PluginChain.php(44): Github\HttpClient\Plugin\GithubExceptionThrower->handleRequest(Object(Nyholm\Psr7\Request), Object(Closure), Object(Http\Client\Common\PluginChain)) #3 vendor/php-http/client-common/src/PluginChain.php(59): Http\Client\Common\PluginChain->Http\Client\Common\{closure}(Object(Nyholm\Psr7\Request)) #4 vendor/php-http/client-common/src/PluginClient.php(87): Http\Client\Common\PluginChain->__invoke(Object(Nyholm\Psr7\Request)) #5 vendor/php-http/client-common/src/HttpMethodsClient.php(148): Http\Client\Common\PluginClient->sendRequest(Object(Nyholm\Psr7\Request)) #6 vendor/php-http/client-common/src/HttpMethodsClient.php(107): Http\Client\Common\HttpMethodsClient->sendRequest(Object(Nyholm\Psr7\Request)) #7 vendor/php-http/client-common/src/HttpMethodsClient.php(55): Http\Client\Common\HttpMethodsClient->send(\'GET\', \'...\', Array, NULL) #8 vendor/knplabs/github-api/lib/Github/Api/AbstractApi.php(92): Http\Client\Common\HttpMethodsClient->get(\'...\', Array) #9 vendor/knplabs/github-api/lib/Github/Api/AcceptHeaderTrait.php(19): Github\Api\AbstractApi->get(\'...\', Array, Array) #10 vendor/knplabs/github-api/lib/Github/Api/Repo.php(150): Github\Api\Repo->get(\'...\') #11 src/Cron/GithubUpdater.php(34): Github\Api\Repo->show(\'foo\', \'core\') #12 src/Cron/GithubUpdater.php(24): App\Cron\GithubUpdater->addRepositoryData(Array) #13 vendor/contao/core-bundle/src/Cron/CronJob.php(44): App\Cron\GithubUpdater->__invoke(\'cli\') #14 vendor/contao/core-bundle/src/Cron/Cron.php(197): Contao\CoreBundle\Cron\CronJob->__invoke(\'cli\') #15 vendor/contao/core-bundle/src/Cron/Cron.php(182): Contao\CoreBundle\Cron\Cron->executeCrons(Array, \'cli\', Object(Closure)) #16 vendor/contao/core-bundle/src/Cron/Cron.php(97): Contao\CoreBundle\Cron\Cron->doRun(Array, \'cli\', false) #17 vendor/contao/core-bundle/src/Command/CronCommand.php(53): Contao\CoreBundle\Cron\Cron->run(\'cli\', false) #18 vendor/symfony/console/Command/Command.php(326): Contao\CoreBundle\Command\CronCommand->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #19 vendor/symfony/console/Application.php(1096): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #20 vendor/symfony/framework-bundle/Console/Application.php(126): Symfony\Component\Console\Application->doRunCommand(Object(Contao\CoreBundle\Command\CronCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #21 vendor/symfony/console/Application.php(324): Symfony\Bundle\FrameworkBundle\Console\Application->doRunCommand(Object(Contao\CoreBundle\Command\CronCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #22 vendor/symfony/framework-bundle/Console/Application.php(80): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #23 vendor/symfony/console/Application.php(175): Symfony\Bundle\FrameworkBundle\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #24 vendor/contao/manager-bundle/bin/contao-console(40): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput)) #25 vendor/bin/contao-console(119): include(\'...\') #26 {main} [] []',
            '2024-08-20T00:00:05.476105+02:00',
            'app',
            'ERROR',
            'Github\Exception\RuntimeException: Bad credentials in vendor/knplabs/github-api/lib/Github/HttpClient/Plugin/GithubExceptionThrower.php:137 Stack trace: #0 vendor/php-http/httplug/src/Promise/HttpFulfilledPromise.php(31): Github\HttpClient\Plugin\GithubExceptionThrower->Github\HttpClient\Plugin\{closure}(Object(Nyholm\Psr7\Response)) #1 vendor/knplabs/github-api/lib/Github/HttpClient/Plugin/GithubExceptionThrower.php(28): Http\Client\Promise\HttpFulfilledPromise->then(Object(Closure)) #2 vendor/php-http/client-common/src/PluginChain.php(44): Github\HttpClient\Plugin\GithubExceptionThrower->handleRequest(Object(Nyholm\Psr7\Request), Object(Closure), Object(Http\Client\Common\PluginChain)) #3 vendor/php-http/client-common/src/PluginChain.php(59): Http\Client\Common\PluginChain->Http\Client\Common\{closure}(Object(Nyholm\Psr7\Request)) #4 vendor/php-http/client-common/src/PluginClient.php(87): Http\Client\Common\PluginChain->__invoke(Object(Nyholm\Psr7\Request)) #5 vendor/php-http/client-common/src/HttpMethodsClient.php(148): Http\Client\Common\PluginClient->sendRequest(Object(Nyholm\Psr7\Request)) #6 vendor/php-http/client-common/src/HttpMethodsClient.php(107): Http\Client\Common\HttpMethodsClient->sendRequest(Object(Nyholm\Psr7\Request)) #7 vendor/php-http/client-common/src/HttpMethodsClient.php(55): Http\Client\Common\HttpMethodsClient->send(\'GET\', \'...\', Array, NULL) #8 vendor/knplabs/github-api/lib/Github/Api/AbstractApi.php(92): Http\Client\Common\HttpMethodsClient->get(\'...\', Array) #9 vendor/knplabs/github-api/lib/Github/Api/AcceptHeaderTrait.php(19): Github\Api\AbstractApi->get(\'...\', Array, Array) #10 vendor/knplabs/github-api/lib/Github/Api/Repo.php(150): Github\Api\Repo->get(\'...\') #11 src/Cron/GithubUpdater.php(34): Github\Api\Repo->show(\'foo\', \'core\') #12 src/Cron/GithubUpdater.php(24): App\Cron\GithubUpdater->addRepositoryData(Array) #13 vendor/contao/core-bundle/src/Cron/CronJob.php(44): App\Cron\GithubUpdater->__invoke(\'cli\') #14 vendor/contao/core-bundle/src/Cron/Cron.php(197): Contao\CoreBundle\Cron\CronJob->__invoke(\'cli\') #15 vendor/contao/core-bundle/src/Cron/Cron.php(182): Contao\CoreBundle\Cron\Cron->executeCrons(Array, \'cli\', Object(Closure)) #16 vendor/contao/core-bundle/src/Cron/Cron.php(97): Contao\CoreBundle\Cron\Cron->doRun(Array, \'cli\', false) #17 vendor/contao/core-bundle/src/Command/CronCommand.php(53): Contao\CoreBundle\Cron\Cron->run(\'cli\', false) #18 vendor/symfony/console/Command/Command.php(326): Contao\CoreBundle\Command\CronCommand->execute(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #19 vendor/symfony/console/Application.php(1096): Symfony\Component\Console\Command\Command->run(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #20 vendor/symfony/framework-bundle/Console/Application.php(126): Symfony\Component\Console\Application->doRunCommand(Object(Contao\CoreBundle\Command\CronCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #21 vendor/symfony/console/Application.php(324): Symfony\Bundle\FrameworkBundle\Console\Application->doRunCommand(Object(Contao\CoreBundle\Command\CronCommand), Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #22 vendor/symfony/framework-bundle/Console/Application.php(80): Symfony\Component\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #23 vendor/symfony/console/Application.php(175): Symfony\Bundle\FrameworkBundle\Console\Application->doRun(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput)) #24 vendor/contao/manager-bundle/bin/contao-console(40): Symfony\Component\Console\Application->run(Object(Symfony\Component\Console\Input\ArgvInput)) #25 vendor/bin/contao-console(119): include(\'...\') #26 {main}',
            '[]',
            '[]',
        ];

        yield [
            '[2024-08-20T06:22:09.276738+02:00] php.WARNING: Warning: Undefined array key "MSC" {"exception":"[object] (ErrorException(code: 0): Warning: Undefined array key \"MSC\" at vendor/contao/newsletter-bundle/contao/modules/ModuleSubscribe.php:95)"} {"request_uri":"https://www.example.org/","request_method":"GET"}',
            '2024-08-20T06:22:09.276738+02:00',
            'php',
            'WARNING',
            'Warning: Undefined array key "MSC"',
            '{"exception":"[object] (ErrorException(code: 0): Warning: Undefined array key \"MSC\" at vendor/contao/newsletter-bundle/contao/modules/ModuleSubscribe.php:95)"}',
            '{"request_uri":"https://www.example.org/","request_method":"GET"}',
        ];

        yield 'Insert tag in log message' => [
            '[2024-08-20T00:41:04.809596+02:00] contao.error.ERROR: Unknown insert tag {{foo::bar}} on page https://www.example.org/ [] {"request_uri":"https://www.example.org/","request_method":"GET","contao":{"Contao\\CoreBundle\\Monolog\\ContaoContext":"{\"func\":\"Contao\\\\InsertTags::executeReplace\",\"action\":\"ERROR\",\"username\":\"N\\/A\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/120.0.0.0 Safari\\/537.36\",\"uri\":\"https:\\/\\/www.example.org\\/\",\"pageId\":32}"}}',
            '2024-08-20T00:41:04.809596+02:00',
            'contao.error',
            'ERROR',
            'Unknown insert tag {{foo::bar}} on page https://www.example.org/',
            '[]',
            '{"request_uri":"https://www.example.org/","request_method":"GET","contao":{"Contao\\CoreBundle\\Monolog\\ContaoContext":"{\"func\":\"Contao\\\\InsertTags::executeReplace\",\"action\":\"ERROR\",\"username\":\"N\\/A\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/120.0.0.0 Safari\\/537.36\",\"uri\":\"https:\\/\\/www.example.org\\/\",\"pageId\":32}"}}',
        ];
    }
}
