<?php
/**
 * Created by PhpStorm.
 * User: yanickwitschi
 * Date: 02.03.16
 * Time: 17:37
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Tenside\Core\Util\JsonArray;


class UiController extends Controller
{
    /**
     * Index action
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Try to find the user language
        $locale = $request->getPreferredLanguage(['de', 'en']);

        if (false === $this->isProjectCreated()) {
            return $this->redirect(
                $this->generateUrl('install', ['_locale' => $locale])
            );
        }
    }

    /**
     * Login action.
     */
    public function loginAction()
    {
        return $this->render('AppBundle::login.html.twig');
    }

    /**
     * Install action.
     */
    public function installAction()
    {
        return $this->render('AppBundle::install.html.twig');
    }

    /**
     * Packages action.
     */
    public function packagesAction()
    {
        return $this->render('AppBundle::packages.html.twig');
    }

    /**
     * Search action.
     */
    public function searchAction()
    {
        return $this->render('AppBundle::search.html.twig');
    }

    /**
     * Asset action.
     * Assets have to be served using PHP because of the PHAR compilation.
     *
     * @var string $path
     */
    public function assetAction($path)
    {
        $webDir = dirname($this->container->getParameter('kernel.root_dir')) . '/web';
        $path = realpath($webDir . '/' . $path);

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
     * @param string $locale
     * @param string $domain
     *
     * @return Response
     */
    public function translationAction($locale, $domain)
    {
        /** @var Translator $translator */
        $translator = $this->container->get('translator');

        try {
            $catalogue = $translator->getCatalogue($locale);
            $response = JsonResponse::create($catalogue->all($domain));

            return $response;
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Could not load translation');
        }
    }

    /**
     * Check if project was created
     *
     * @return bool
     */
    private function isProjectCreated()
    {
        try {
            $res = $this->forward('TensideCoreBundle:InstallProject:getInstallationState');
            $json = new JsonArray($res->getContent());
            if ('OK' === $json->get('status')
                && true === $json->get('state/tenside_configured')
                && true === $json->get('state/project_created')
                && true === $json->get('state/project_installed')
            ) {

                return true;
            }
        } catch (\Exception $e) {}

        return false;
    }
}
