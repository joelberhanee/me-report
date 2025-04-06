<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiControllerJson
{
    #[Route("/api/quote", name: "api_quote")]
    public function quote(): JsonResponse
    {
        // Dina egna citat
        $quotes = [
            "Nar du lyckas viskar dom, nar du misslyckas skriker dom.",
            "Man ar inte vacker forran man ar vacker nyvaken.",
            "En blomma har aldrig blommat pa en dag."
        ];

        $quote = $quotes[array_rand($quotes)];

        $date = new \DateTime();
        $timestamp = $date->getTimestamp();

        $data = [
            'quote' => $quote,
            'date' => $date->format('Y-m-d'),
            'timestamp' => $timestamp,
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );

        return $response;
    }
}
