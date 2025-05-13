<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebcamController extends AbstractController
{
    #[Route('/webcam', name: 'app_webcam')]
    public function index(): Response
    {
        return $this->render('webcam.html.twig');
    }
}
