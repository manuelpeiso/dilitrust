<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ChangeLanguageController extends AbstractController
{
    private $requestStack;
    private $adminUrlGenerator;

    public function __construct(RequestStack $requestStack, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    #[Route('/change/language/{locale}', name: 'change_language', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $locale = $request->get('locale');
        $this->requestStack->getSession()->set('_locale', $locale);
        $request->setLocale($locale);

        $request->getSession()->set('_locale', $locale);

        $url = $this->adminUrlGenerator
            ->setRoute('admin')
            ->generateUrl();

        return $this->redirect($url);
    }
}
