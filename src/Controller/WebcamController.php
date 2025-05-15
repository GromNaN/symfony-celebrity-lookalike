<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebcamController extends AbstractController
{
    #[Route('/webcam', name: 'app_webcam')]
    public function index(): Response
    {
        return $this->render('webcam.html.twig');
    }

    #[Route('/webcam/store', name: 'app_webcam_store', methods: ['POST'])]
    public function store(Request $request, PictureService $pictureService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (! isset($data['image'])) {
            return new JsonResponse(['error' => 'No image provided'], 400);
        }

        // Decode base64 image
        $imageData = $data['image'];
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);
            $ext = strtolower($type[1]);
            if (! in_array($ext, ['jpg', 'jpeg', 'png'])) {
                return new JsonResponse(['error' => 'Invalid image type'], 400);
            }
        } else {
            return new JsonResponse(['error' => 'Invalid image data'], 400);
        }

        // Save to a temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), 'webcam_') . '.' . $ext;
        try {
            file_put_contents($tmpFile, $imageData);
            $face = $pictureService->storePicture($tmpFile, 'webcam.' . $ext, '', true);
        } finally {
            @unlink($tmpFile);
        }

        $url = $this->generateUrl('face_show', ['id' => $face->id]);

        return new JsonResponse(['url' => $url]);
    }
}
