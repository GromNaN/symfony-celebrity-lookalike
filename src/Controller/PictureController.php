<?php

namespace App\Controller;

use App\Document\Picture;
use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PictureController extends AbstractController
{
    public function __construct(private PictureService $pictureService)
    {
    }

    #[Route('/process', name: 'process_picture', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $file = $request->files->get('picture');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        // Store the image and create a Picture document
        $picture = $this->pictureService->storePicture($file->getPathname(), $file->getClientOriginalName());

        // Find matches based on embeddings
        $matches = $this->pictureService->findSimilarPictures($picture->embeddings);

        return $this->json([
            'message' => 'Picture processed successfully',
            'matches' => $matches
        ]);
    }
}
