<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontendController extends AbstractController
{
    #[Route('/', name: 'app_frontend', methods: ['GET'])]
    public function index(): Response
    {
        // Checking if there is a user login
        $user = $this->getUser();
        if ($user) {
            // If there is a user login redirect to file management section
            return $this->redirectToRoute('admin');
        } else {
            // If there is not a user login redirect to login section
            return $this->redirectToRoute('login');
        }
    }
}
