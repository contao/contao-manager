<?php

/**
 * This file is part of contao/contao-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/contao-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2016 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/contao-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/contao-manager
 * @filesource
 */

namespace AppBundle\Test;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class tests the Uicontroller
 */
class UiControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        TestHomePathDeterminator::setHomePath($this->getTempDir());
    }

    /**
     * Provides the datasets for the index action.
     *
     * @return array
     */
    public function indexActionRedirect()
    {
        return [
            ['de,en-US;q=0.8,en;q=0.6', '/de'],
            ['', '/en'],
            ['en-US;q=0.8,en;q=0.6', '/en'],
            ['jp', '/en'],
        ];
    }

    /**
     * Test the index action.
     *
     * @param string $acceptHeader The accept header to use.
     *
     * @param string $url          The expected URL to redirect to.
     *
     * @return void
     *
     * @dataProvider indexActionRedirect
     */
    public function testIndexActionRedirects($acceptHeader, $url)
    {
        $client = $this->createClient();
        $client->request('GET', '/', [], [], ['HTTP_Accept-Language' => $acceptHeader]);

        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect($url));
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test the index action.
     *
     * @return void
     */
    public function testUnknownRedirectsToIndex()
    {
        $client = $this->createClient();
        $client->request('GET', 'https://tenside.example.com/does-not-exist', [], [], []);

        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect('https://tenside.example.com/'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test the app action.
     *
     * @return void
     */
    public function testAppAction()
    {
        $client   = $this->createClient();
        $crawler  = $client->request('GET', 'https://tenside.example.com/de');
        $response = $client->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals('de', $crawler->filterXPath('//html')->attr('lang'));
        $this->assertEquals('/', $crawler->filterXPath('//base')->attr('href'));
        $this->assertEquals('/web-assets/css/bundle.css', $crawler->filterXPath('//link')->attr('href'));
        $this->assertEquals('/web-assets/js/bundle.js', $crawler->filterXPath('//script')->attr('src'));
    }

    /**
     * Test the app action (from within a phar file).
     *
     * @return void
     */
    public function testAppActionFromPharFile()
    {
        $client   = $this->createClient();
        $crawler  = $client->request(
            'GET',
            'https://tenside.example.com/contao-manager.phar/de',
            [],
            [],
            ['SCRIPT_FILENAME' => '/var/www/web/contao-manager.phar',]
        );
        $response = $client->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            'de',
            $crawler->filterXPath('//html')->attr('lang')
        );
        $this->assertEquals(
            '/contao-manager.phar/',
            $crawler->filterXPath('//base')->attr('href')
        );
        $this->assertEquals(
            '/contao-manager.phar/web-assets/css/bundle.css',
            $crawler->filterXPath('//link')->attr('href')
        );
        $this->assertEquals(
            '/contao-manager.phar/web-assets/js/bundle.js',
            $crawler->filterXPath('//script')->attr('src')
        );
    }

    /**
     * Provide the file paths for the asset action tests.
     *
     * @return array
     */
    public function assetActionProvider()
    {
        return [
            [
                'file' => 'css/bundle.css',
                'mime' => 'text/css'
            ],
            [
                'file' => 'js/bundle.js',
                'mime' => 'text/javascript'
            ],
            [
                'file' => 'images/logo.svg',
                'mime' => 'image/svg+xml'
            ],
        ];
    }

    /**
     * Test the asset action.
     *
     * @param string $file         The file to request.
     *
     * @param string $expectedMime The mime type that shall be reported.
     *
     * @return void
     *
     * @dataProvider assetActionProvider
     */
    public function testAssetAction($file, $expectedMime)
    {
        $fullPath = realpath(__DIR__ . '/../../src/Resources/public/' . $file);

        if (!is_file($fullPath)) {
            $this->markTestSkipped('File ' . $fullPath . ' could not be found, missing asset?');
            return;
        }

        $client = $this->createClient();

        $client->request('GET', 'https://tenside.example.com/web-assets/' . $file);
        $response = $client->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals($fullPath, $response->getFile()->getPathname());
        $this->assertEquals($expectedMime, $response->headers->get('Content-Type'));
    }

    /**
     * Test the asset action (from within a phar file).
     *
     * @param string $file         The file to request.
     *
     * @param string $expectedMime The mime type that shall be reported.
     *
     * @return void
     *
     * @dataProvider assetActionProvider
     */
    public function testAssetActionFromPharFile($file, $expectedMime)
    {
        $fullPath = realpath(__DIR__ . '/../../src/Resources/public/' . $file);

        if (!is_file($fullPath)) {
            $this->markTestSkipped('File ' . $fullPath . ' could not be found, missing asset?');
            return;
        }

        $client = $this->createClient();

        $client->request(
            'GET',
            'https://tenside.example.com/contao-manager.phar/web-assets/' . $file,
            [],
            [],
            ['SCRIPT_FILENAME' => '/var/www/web/contao-manager.phar',]
        );
        $response = $client->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals($fullPath, $response->getFile()->getPathname());
        $this->assertEquals($expectedMime, $response->headers->get('Content-Type'));
    }

    /**
     * Test the asset action for an invalid file.
     *
     * @return void
     */
    public function testUnknownAssetActionFailsWithException()
    {
        $client = $this->createClient();

        $client->request('GET', 'https://tenside.example.com/web-assets/images/non-existent.file');
        $response = $client->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            ['status' => 'ERROR', 'message' => 'This asset does not exist!'],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * Test that the translation action returns a message catalog.
     *
     * @return void
     */
    public function testTranslationAction()
    {
        $client = $this->createClient();

        $client->request('GET', 'https://tenside.example.com/translation/de/install');
        $response = $client->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue(is_array($content));
        $this->assertNotEmpty($content);
    }

    /**
     * Test that the translation action returns an error for unknown catalog.
     *
     * @return void
     */
    public function testTranslationActionForUnknownCatalog()
    {
        $client = $this->createClient();

        $client->request('GET', 'https://tenside.example.com/translation/invalid-locale!/do-not-care');
        $response = $client->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            ['status' => 'ERROR', 'message' => 'Could not load translation'],
            json_decode($response->getContent(), true)
        );
    }
}
