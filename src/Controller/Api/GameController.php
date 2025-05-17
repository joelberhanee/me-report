<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/api/game', name: 'api_game', methods: ['GET'])]
    public function gameStatus(SessionInterface $session): JsonResponse
    {
        return new JsonResponse([
            'player_sum' => $session->get('player_sum'),
            'bank_sum' => $session->get('bank_sum'),
            'status' => $session->get('status'),
            'scoreboard' => $session->get('scoreboard'),
        ]);
    }
}
