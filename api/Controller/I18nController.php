<?php

namespace Contao\ManagerApi\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorBagInterface;

class I18nController extends Controller
{

    public function getAction(Request $request)
    {
        /** @var TranslatorBagInterface $translator */
        $translator = $this->get('translator');
        $locale = $request->attributes->get('locale');

        $catalogue = $translator->getCatalogue($locale);
        $messages = $catalogue->all('ui');

        if (empty($messages) && 5 === strlen($locale)) {
            $catalogue = $translator->getCatalogue(substr($locale, 0, 2));
            $messages = $catalogue->all('ui');
        }

        while ($catalogue = $catalogue->getFallbackCatalogue()) {
            $messages = array_replace_recursive($catalogue->all('ui'), $messages);
        }

        return new JsonResponse($messages);
    }
}
