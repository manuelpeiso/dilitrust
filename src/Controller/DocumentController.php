<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Document;
use App\Form\DocumentFormType;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;

/**
* @Route(
*     "/{_locale}/admin/document",
*     name="document_",
*     requirements={
*         "_locale": "en|es",
*     }
* )
*/
class DocumentController extends AbstractController
{
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $errors = $request->get('errors');
        // getting the document repository
        $repository = $doctrine->getRepository(Document::class);

        //gerring the logged user
        $user = $this->getUser();

        //getting the documents uploaded by the logged user
        $documents = $repository->findBy([
            'user' => $user
        ]);

        return $this->render('document/index.html.twig', [
            'documents' => $documents,
            'errors' => $errors
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        //gettign the entity manager
        $entityManager = $doctrine->getManager();

        //getting the logged user
        $user = $this->getUser();

        //creating a new document owned by the logged user
        $document = new Document();
        $document->setUser($user);

        //creating the form related to the document
        $form = $this->createForm(DocumentFormType::class, $document);

        //handling the request
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            // getting the file
            $file = $form->get('file')->getData();

            //getting the original filename
            $originalName = $file->getClientOriginalName();

            //generating a unique name for the file
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            //moving the file to the upload directory
            $cvDir = $this->getParameter('kernel.project_dir').'/public/uploads/files';
            $file->move($cvDir, $fileName);

            //updatign the document values
            $document->setFile($fileName);
            $document->setName($originalName);

            //saving the document in yhe database
            $entityManager->persist($document);
            $entityManager->flush();

            //redirecting to the document list
            $url = $this->adminUrlGenerator
            ->setRoute('document_list', [
                'errors' => array()
            ])
            ->generateUrl();

            return $this->redirect($url);
        }

        return $this->renderForm('document/file.html.twig', array(
            'form' => $form
        ));
    }

    #[Route('/del/{name}', name: 'del', methods: ['GET'])]
    public function delete(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $errors = array();

        // gettign the filename
        $name = $request->get('name');

        // getting the document repository
        $repository = $doctrine->getRepository(Document::class);

        //checking if the file exist for the user
        $document = $repository->findOneBy([
            'file' => $name,
            'user' => $this->getUser()
        ]);

        //if the user have the specified file
        if ($name && $document) {
            //getting the entity manager
            $entityManager = $doctrine->getManager();
            
            // getting the document repository
            $repository = $doctrine->getRepository(Document::class);

            //deleting the document in the database
            $entityManager->remove($document);
            $entityManager->flush();

            //deleting the document in the filesystem
            $document_dir = $this->getParameter('kernel.project_dir').'/public/uploads/files/'.$name;
            unlink($document_dir);            
        } else {
            $errors[] = 'You have not access to this file';
        }
        
        //redirecting to the document list
        $url = $this->adminUrlGenerator
        ->setRoute('document_list',[
            'errors' => $errors
        ])
        ->generateUrl();
        return $this->redirect($url);
    }

    #[Route('/download/{name}', name: 'download', methods: ['GET'])]
    public function download(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $errors = array();

        //getting the filename
        $name = $request->get('name');

        //getting the document repository
        $repository = $doctrine->getRepository(Document::class);

        //checking if the file exist for the user
        $document = $repository->findOneBy([
            'file' => $name,
            'user' => $this->getUser()
        ]);

        //if the user have the specified file
        if ($name && $document) {
            //downloading the file
            $document_dir = $this->getParameter('kernel.project_dir').'/public/uploads/files/'.$name;
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($document_dir) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($document_dir));
            readfile($document_dir);
            exit;
        }
        $errors[] = 'You have not access to this file';

        //redirecting to the document list
        $url = $this->adminUrlGenerator
            ->setRoute('document_list', [
                'errors' => $errors
            ])
            ->generateUrl();

        return $this->redirect($url);
    }
}
