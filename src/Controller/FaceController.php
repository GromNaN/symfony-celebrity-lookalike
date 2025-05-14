<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\Face;
use App\Form\UploadForm;
use App\Service\PictureService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaceController extends AbstractController
{
    #[Route('/picture/{id}', name: 'face_picture', methods: ['GET'])]
    public function picture(Face $face): Response
    {
        return new Response(
            $face->resizedImage,
            headers: [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline',
                'Content-Length' => (string) strlen($face->resizedImage),
            ],
        );
    }

    #[Route('/faces', name: 'face_list', methods: ['GET'])]
    public function list(DocumentManager $dm): Response
    {
        $faces = $dm->getRepository(Face::class)->findAll();

        return $this->render('face/list.html.twig', ['faces' => $faces]);
    }

    #[Route('/faces/{id}', name: 'face_show', methods: ['GET'])]
    public function show(Face $face, PictureService $pictureService): Response
    {
        return $this->render('face/show.html.twig', [
            'face' => $face,
            'similar_faces' => $pictureService->findSimilarPictures($face),
        ]);
    }

    #[Route('/upload', name: 'face_upload', methods: ['GET', 'POST'])]
    public function upload(PictureService $pictureService, Request $request): Response
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

        return $this->render('face/upload.html.twig', ['form' => $form->createView()]);
    }
}
