<?php

/**
 * This file is part of tenside/ui.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/ui
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/ui
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Ui\Web\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Web\Controller\AbstractController;

/**
 * Provide access to all embedded asset files.
 */
class UiController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        static::createRoute($routes, 'rootRedirect', '/');
        static::createRoute($routes, 'index', '/index.html');
        static::createRoute($routes, 'install', '/install.html');

        static::createRoute(
            $routes,
            'asset',
            '/{path}/{file}',
            ['GET'],
            [
                'path' => 'css|fonts|img|js|pages|l10n',
                'file' => '[\-\_a-zA-Z]*(\.[a-zA-Z]*)+'
            ]
        );
    }

    /**
     * Redirect any request to the "/" path to the index.html file.
     *
     * @param Request $request The request to process.
     *
     * @return RedirectResponse
     */
    public function rootRedirectAction(Request $request)
    {
        $uri = $request->getUri();
        // Special case, not correctly setup yet. Do so now.
        if (!$this->getTenside()->isInstalled()) {
            return new RedirectResponse($uri . 'install.html');
        }

        return new RedirectResponse($uri . 'index.html');
    }

    /**
     * Provide the index.html file.
     *
     * @param Request $request The request to process.
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Special case, not correctly setup yet. Do so now.
        if (!$this->getTenside()->isInstalled()) {
            return new RedirectResponse($request->getUri() . 'install.html');
        }

        return new Response(
            str_replace(
                'var TENSIDEApi=window.location.href.split(\'#\')[0];',
                'var TENSIDEApi=\'' . $request->getSchemeAndHttpHost() . $request->getBaseUrl() . '/\';',
                file_get_contents($this->getAssetsDir() . '/index.html')
            ),
            200,
            array(
                'Content-Type' => 'text/html; charset=UTF-8'
            )
        );
    }

    /**
     * Provide the install.html file.
     *
     * @param Request $request The request to process.
     *
     * @return Response
     */
    public function installAction(Request $request)
    {
        // Special case, already setup. Redirect to index then.
        if ($this->getTenside()->isInstalled()) {
            return new RedirectResponse($request->getUri() . 'index.html');
        }

        return new Response(
            str_replace(
                'var TENSIDEApi=window.location.href.split(\'#\')[0];',
                'var TENSIDEApi=\'' . $request->getSchemeAndHttpHost() . $request->getBaseUrl() . '/\';',
                file_get_contents($this->getAssetsDir() . '/install.html')
            ),
            200,
            array(
                'Content-Type' => 'text/html; charset=UTF-8'
            )
        );
    }

    /**
     * Provide an asset.
     *
     * @param string $path The assets sub dir.
     *
     * @param string $file The file name within the sub dir..
     *
     * @return Response
     */
    public function assetAction($path, $file)
    {
        $filePath = $this->getAssetsDir() . '/' . $path . '/' . $file;

        if (file_exists($filePath)) {
            $response = new Response(file_get_contents($filePath));
            $mime     = $this->getMime($filePath);
            if ($mime !== null) {
                $response->headers->set('Content-Type', $mime);
            }

            return $response;
        }

        return new Response($filePath . ' not found', 404);
    }

    /**
     * Retrieve the mime type of a file.
     *
     * @param string $filePath The filename for which the mime shall be guessed.
     *
     * @return string|null
     */
    private function getMime($filePath)
    {
        if (substr($filePath, -3) === '.js') {
            return 'text/javascript; charset=UTF-8';
        }

        if (substr($filePath, -5) === '.html') {
            return 'text/html; charset=UTF-8';
        }

        if (substr($filePath, -4) === '.css') {
            return 'text/css; charset=UTF-8';
        }

        if (substr($filePath, -4) === '.png') {
            return 'image/png';
        }

        if (substr($filePath, -4) === '.svg') {
            return 'image/svg+xml';
        }

        if (substr($filePath, -5) === '.woff') {
            return 'application/font-woff';
        }

        if ((substr($filePath, -4) === '.map')
            || (substr($filePath, -4) === '.json')) {
            return 'application/json';
        }

        return null;
    }

    /**
     * Retrieve the assets dir.
     *
     * @return string
     *
     * @throws \RuntimeException When the assets dir can not be located.
     */
    private function getAssetsDir()
    {
        if ($phar = \Phar::running()) {
            return $phar . '/assets';
        }

        // FIXME: hardcoded assets path for non phar mode - change this!
        $dir = dirname(__DIR__);
        while (($dir = dirname($dir)) !== '.') {
            if (is_dir($dir . '/.build')) {
                return $dir . '/.build';
            }
        }

        throw new \RuntimeException('Could not find assets directory.');
    }
}
