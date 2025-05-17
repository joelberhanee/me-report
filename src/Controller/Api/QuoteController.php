<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class QuoteController extends AbstractController
{
    #[Route("/api/quote", name: "api_quote", methods: ['GET'])]
    public function quote(): JsonResponse
    {
        $quotes = [
            "Nar du lyckas viskar dom, nar du misslyckas skriker dom.",
            "Man ar inte vacker forran man ar vacker nyvaken.",
            "En blomma har aldrig blommat pa en dag."
        ];

        $quote = $quotes[array_rand($quotes)];
        $date = new \DateTime();

        return $this->json([
            'quote' => $quote,
            'date' => $date->format('Y-m-d'),
            'timestamp' => $date->getTimestamp(),
        ]);
    }
}
