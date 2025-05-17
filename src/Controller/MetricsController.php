<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MetricsController extends AbstractController
{
    #[Route('/metrics', name: 'metrics')]
    public function index(): Response
    {
        return $this->render('metrics/index.html.twig', [
            'controller_name' => 'MetricsController',
        ]);
    }
}
