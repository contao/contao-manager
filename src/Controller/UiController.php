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
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/contao-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/contao-manager
 * @filesource
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Translation\Translator;

/**
 * This is the controller for the web interface.
 */
class UiController extends Controller
{
    /**
     * Index action
     *
     * @param Request $request The request.
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Try to find the user language
        $locale = $request->getPreferredLanguage(['de', 'en']);

        return $this->redirect($this->generateUrl('app', ['locale' => $locale]));
    }

    /**
     * App action.
     *
     * @param string $locale The locale.
     *
     * @return Response
     */
    public function appAction($locale)
    {
        return $this->render('AppBundle::index.html.php', [
            'lang'  => $locale,
            'css'   => $this->generateUrl('asset', ['path' => 'css/bundle.css']),
            'js'    => $this->generateUrl('asset', ['path' => 'js/bundle.js']),
        ]);
    }

    /**
     * Asset action.
     *
     * Assets have to be served using PHP because of the PHAR compilation.
     *
     * @param string $path The path.
     *
     * @return BinaryFileResponse
     *
     * @throws BadRequestHttpException When the asset does not exist.
     */
    public function assetAction($path)
    {
        /** @var \Symfony\Component\HttpKernel\Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $path   = $kernel->locateResource('@AppBundle/Resources/public/' . $path);

        if (false === $path) {
            throw new BadRequestHttpException('This asset does not exist!');
        }

        $file = new \SplFileInfo($path);

        $response = new BinaryFileResponse($file);
        $response->setAutoLastModified();
        $response->setAutoEtag();

        // Try to be explicit about our own assets
        switch ($file->getExtension()) {
            case 'css':
                $response->headers->set('Content-Type', 'text/css');
                break;
            case 'js':
                $response->headers->set('Content-Type', 'text/javascript');
                break;
            case 'svg':
                $response->headers->set('Content-Type', 'image/svg+xml');
                break;
            default:
                // Otherwise, let the Symfony file mime type guesser do the work
        }

        return $response;
    }


    /**
     * Translation action.
     *
     * @param Request $request The request.
     *
     * @param string  $locale  The locale string.
     *
     * @param string  $domain  The current domain.
     *
     * @return Response
     *
     * @throws BadRequestHttpException When a translation could not be loaded.
     */
    public function translationAction(Request $request, $locale, $domain)
    {
        /** @var Translator $translator */
        $translator = $this->container->get('translator');

        try {
            $catalogue = $translator->getCatalogue($locale);
            $data      = $catalogue->all($domain);
            $cacheKey  = md5(json_encode($data));
            $response  = JsonResponse::create($data);
            $response->setEtag($cacheKey);
            $response->isNotModified($request);

            return $response;
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Could not load translation');
        }
    }
}
