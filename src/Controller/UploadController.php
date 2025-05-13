<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\UploadForm;
use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    #[Route('/upload', name: 'upload_picture', methods: ['GET', 'POST'])]
    public function __invoke(PictureService $pictureService, Request $request): Response
    {
        $form = $this->createForm(UploadForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form->get('name')->getData();
            $picture = $form->get('picture')->getData();

            $pictureService->storePicture($picture->getPathname(), $picture->getClientOriginalName(), $name);

            $this->addFlash('success', 'File uploaded successfully!');

            // Recreate form for the next upload
            $form = $this->createForm(UploadForm::class);
        }

        return $this->render('upload.html.twig', ['form' => $form->createView()]);
    }
}
