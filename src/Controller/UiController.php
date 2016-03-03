<?php
/**
 * Created by PhpStorm.
 * User: yanickwitschi
 * Date: 02.03.16
 * Time: 17:37
 */

namespace AppBundle\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Core\Util\JsonArray;


class UiController extends Controller
{
    /**
     * Index action
     */
    public function indexAction()
    {
        if (false === $this->isProjectCreated()) {
            return $this->redirect(
                $this->generateUrl('install')
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
