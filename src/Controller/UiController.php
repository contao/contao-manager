<?php
/**
 * Created by PhpStorm.
 * User: yanickwitschi
 * Date: 02.03.16
 * Time: 17:37
 */

namespace AppBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;


class UiController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

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
     * Render a template with given parameters and response.
     *
     * @param               $view
     * @param array         $parameters
     * @param Response|null $response
     *
     * @return Response
     */
    private function render($view, array $parameters = [], Response $response = null)
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->container->get('twig')->render($view, $parameters));

        return $response;
    }
}
