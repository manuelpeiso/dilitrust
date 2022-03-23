<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Controller\UserController;
use App\Controller\DocumentController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
* @Route(
*     "/{_locale}",
*     requirements={
*         "_locale": "en|es",
*     }
* )
*/
class DashboardController extends AbstractDashboardController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('admin/dashboard.html.twig', []);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Dilitrust');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard($this->translator->trans('Dashboard'), 'fa fa-home');
        yield MenuItem::section($this->translator->trans('Users'));
        yield MenuItem::linkToRoute($this->translator->trans('Users'), 'fa fa-user', 'user_list');
        yield MenuItem::linkToRoute($this->translator->trans('Files'), 'fa fa-file', 'document_list');
        // yield MenuItem::linkToRoute('Files', 'fa fa-file', 'document_add');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
