<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\UserFormType;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
* @Route(
*     "/{_locale}/admin/user",
*     name="user_",
*     requirements={
*         "_locale": "en|es",
*     }
* )
*/
class UserController extends AbstractController
{
    private $adminUrlGenerator;
    private $translator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, TranslatorInterface $translator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->translator = $translator;
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        // getting the user repository
        $repository = $doctrine->getRepository(User::class);

        //getting all users
        $users = $repository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            $request->query->all()
        ]);
    }

    #[Route('/del/{id}', name: 'del', methods: ['GET'])]
    public function delete(Request $request, ManagerRegistry $doctrine, User $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($user) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($user);
            $entityManager->flush();

            $url = $this->adminUrlGenerator
            ->setRoute('user_list')
            ->generateUrl();

            return $this->redirect($url);
        }
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $userPasswordHasher, User $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $doctrine->getManager();

        if ($user) {
            $form = $this->createForm(UserFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($form->get('plainPassword')->getData() != '') {
                    $user->setPassword(
                        $userPasswordHasher->hashPassword(
                            $user,
                            $form->get('plainPassword')->getData()
                        )
                    );
                }
        
                $entityManager->persist($user);
                $entityManager->flush();

                $url = $this->adminUrlGenerator
                ->setRoute('user_list')
                ->generateUrl();

                return $this->redirect($url);
            }

            $message = $this->translator->trans('Edit user');

            return $this->render('user/form.html.twig', [
                'form' => $form->createView(),
                'message' => $message
            ]);
        }
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $doctrine->getManager();

        $user = new User();
        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);        

        if ($form->isSubmitted() && $form->isValid()) {                
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
        
            $entityManager->persist($user);
            $entityManager->flush();

            $url = $this->adminUrlGenerator
                ->setRoute('user_list')
                ->generateUrl();

            return $this->redirect($url);
        }

        $message = $this->translator->trans('New user');

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'message' => $message
        ]);
    }
}
