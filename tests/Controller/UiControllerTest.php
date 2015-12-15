<?php

/**
 * This file is part of contao/package-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/package-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/package-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/package-manager
 * @filesource
 */

namespace AppBundle\Test\Controller;

use AppBundle\Controller\UiController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This class tests the UiController.
 */
class UiControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the root action is redirected.
     *
     * @return void
     */
    public function testRootRedirectAction()
    {
        $request = $this->getMock('Symfony\\Component\\HttpFoundation\\Request', ['getUri']);
        $request->method('getUri')->willReturn('https://mywebsite.example.com/subdir/');

        $controller = new UiController();

        $response = $controller->rootRedirectAction($request);

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertTrue($response->headers->has('location'));
        $this->assertEquals('https://mywebsite.example.com/subdir/index.html', $response->headers->get('location'));
    }

    /**
     * Test that assets are retrieved correctly.
     *
     * @return void
     */
    public function testIndex()
    {
        $request = $this->getMock('Symfony\\Component\\HttpFoundation\\Request', ['getUri']);
        $request->method('getUri')->willReturn('https://mywebsite.example.com/subdir/');

        $container = new Container();
        $kernel    = $this->getKernel(['locateResource']);
        $kernel->method('locateResource')->willReturn(
            $this->getFixturesPath(DIRECTORY_SEPARATOR . 'index.html')
        );
        $container->set('kernel', $kernel);

        $controller = new UiController();
        $controller->setContainer($container);

        $response = $controller->indexAction($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test that assets are retrieved correctly.
     *
     * @return void
     */
    public function testRetrieval()
    {
        $request   = new Request();
        $container = new Container();
        $kernel    = $this->getKernel(['locateResource']);
        $kernel->method('locateResource')->willReturn(
            $this->getFixturesPath('css' . DIRECTORY_SEPARATOR . 'test.css')
        );
        $container->set('kernel', $kernel);

        $controller = new UiController();
        $controller->setContainer($container);

        $response = $controller->assetAction('css', 'test.css', $request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test that assets are retrieved correctly.
     *
     * @return void
     */
    public function testRetrievalOfUnknown()
    {
        $request   = new Request();
        $container = new Container();
        $kernel    = $this->getKernel(['locateResource']);
        $kernel->method('locateResource')->willReturn(
            $this->getFixturesPath('css' . DIRECTORY_SEPARATOR . 'not-exist.css')
        );
        $container->set('kernel', $kernel);

        $controller = new UiController();
        $controller->setContainer($container);

        $response = $controller->assetAction('css', 'test.css', $request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Test that assets are retrieved correctly.
     *
     * @return void
     */
    public function testNotModified()
    {
        $request   = new Request();
        $container = new Container();
        $kernel    = $this->getKernel(['locateResource']);
        $filePath  = $this->getFixturesPath('css' . DIRECTORY_SEPARATOR . 'test.css');
        $kernel->method('locateResource')->willReturn($filePath);
        $container->set('kernel', $kernel);

        $controller = new UiController();
        $controller->setContainer($container);

        $lastModified = filemtime($filePath);
        $request->headers->set('if_none_match', '"' . md5($filePath . $lastModified) . '"');

        $response = $controller->assetAction('css', 'test.css', $request);

        $this->assertEquals(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Returns a mock for the abstract kernel.
     *
     * @param array $methods Additional methods to mock (besides the abstract ones).
     *
     * @param array $bundles Bundles to register.
     *
     * @return Kernel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getKernel(array $methods = [], array $bundles = [])
    {
        $methods[] = 'registerBundles';

        $kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->setMethods($methods)
            ->setConstructorArgs(array('test', false))
            ->getMockForAbstractClass();
        $kernel->expects($this->any())
            ->method('registerBundles')
            ->will($this->returnValue($bundles));

        $rootDir = new \ReflectionProperty($kernel, 'rootDir');
        $rootDir->setAccessible(true);
        $rootDir->setValue($kernel, $this->getFixturesPath());

        return $kernel;
    }

    /**
     * Retrieve the path of fixtures.
     *
     * @param string $path The path.
     *
     * @return string
     */
    protected function getFixturesPath($path = '')
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
