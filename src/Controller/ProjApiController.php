<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Game\GameProj;

class ProjApiController extends AbstractController
{
    #[Route("proj/api", name: "proj_api")]
    public function apiOverview(): Response
    {
        return $this->render('proj_api.html.twig');
    }

    #[Route('/proj', name: 'proj_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $session = $request->getSession();

        return $this->render('proj/index.html.twig', [
            'old_name' => $session->get('form_name', ''),
            'old_balance' => $session->get('form_balance', ''),
        ]);
    }

    // Ta emot formulärdata och spara i session (POST)
    #[Route('/proj/save', name: 'proj_save_player', methods: ['POST'])]
    public function savePlayer(Request $request, SessionInterface $session): JsonResponse
    {
        $name = $request->request->get('name');
        $balance = (int) $request->request->get('balance');

        if (!$name || $balance < 0) {
            return $this->json(['error' => 'Ogiltiga värden'], 400);
        }

        // Spara i session
        $session->set('player_name', $name);
        $session->set('player_balance', $balance);

        // Spara även för återfyllnad
        $session->set('form_name', $name);
        $session->set('form_balance', $balance);

        return $this->json([
            'message' => 'Spelardata sparad',
            'player_name' => $name,
            'player_balance' => $balance,
        ]);
    }

    #[Route('/proj/api/player', name: 'api_player', methods: ['GET'])]
    public function apiPlayer(SessionInterface $session): JsonResponse
    {
        $name = $session->get('player_name', null);
        $balance = $session->get('player_balance', null);

        if ($name === null || $balance === null) {
            return $this->json(['error' => 'Ingen spelardata i sessionen'], 404);
        }

        return $this->json([
            'player_name' => $name,
            'player_balance' => $balance,
        ]);
    }

    #[Route('/proj/api/draw', name: 'api_draw', methods: ['POST'])]
    public function apiDraw(SessionInterface $session, GameProj $game): JsonResponse
    {
        $message = $game->draw($session);

        $playerHandsObjects = $session->get('player_hands', []);
        $playerHands = [];
        foreach ($playerHandsObjects as $hand) {
            $playerHands[] = $hand->getCards();
        }

        return $this->json([
            'message' => $message,
            'player_hands' => $playerHands,
            'status' => $session->get('status', ''),
            'active_hand_index' => $session->get('active_hand_index', 0),
        ]);
    }

    #[Route('/proj/api/stay', name: 'api_stay', methods: ['POST'])]
    public function apiStay(SessionInterface $session, GameProj $game): JsonResponse
    {
        $message = $game->stay($session);

        return $this->json([
            'message' => $message,
            'status' => $session->get('status', ''),
            'active_hand_index' => $session->get('active_hand_index', 0),
        ]);
    }

    #[Route('/proj/api/start', name: 'api_start', methods: ['POST'])]
    public function apiStart(Request $request, SessionInterface $session, GameProj $game): JsonResponse
    {
        $hands = (int) $request->request->get('hands', 1);
        $bet = (int) $request->request->get('bet', 10);

        if ($hands < 1 || $hands > 3) {
            return $this->json(['error' => 'Antal händer måste vara mellan 1 och 3.'], 400);
        }

        if ($bet < 1) {
            return $this->json(['error' => 'Insats måste vara minst 1.'], 400);
        }

        $balance = $session->get('player_balance', 0);

        if ($balance < $bet * $hands) {
            return $this->json(['error' => 'Otillräckligt saldo för denna insats och antal händer.'], 400);
        }

        $session->set('hands', $hands);
        $session->set('bet', $bet);
        $session->set('player_balance', $balance - $bet * $hands);

        // Rensa gammal speldata
        $session->remove('deck');
        $session->remove('player_hands');
        $session->remove('bank');
        $session->remove('status');
        $session->remove('showBank');
        $session->remove('player_sums');
        $session->remove('bank_sum');
        $session->remove('active_hand_index');
        $session->remove('scoreboard');

        $game->start($session, $hands);

        return $this->json([
            'message' => "Spelet startat med $hands händer och insats $bet kr.",
            'hands' => $hands,
            'bet' => $bet,
            'balance' => $session->get('player_balance')
        ]);
    }

    #[Route('/proj/api/reset', name: 'api_reset', methods: ['POST'])]
    public function apiReset(SessionInterface $session): JsonResponse
    {
        $session->clear();

        return $this->json([
        'message' => 'Sessionen har återställts. Alla speldata är borttagna.'
        ]);
    }
}
