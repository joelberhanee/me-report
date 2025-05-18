<?php

namespace App\Controller;

use App\Card\CardHand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Game\GameTwentyOne;

class TwentyOneController extends AbstractController
{
    #[Route('/game', name: 'game_index')]
    public function index(): Response
    {
        // Visa startsidan för spelet
        return $this->render('game/index.html.twig');
    }

    #[Route('/game/start', name: 'game_start')]
    public function start(SessionInterface $session, GameTwentyOne $game): Response
    {
        // Initiera nytt spel och lagra status i sessionen
        $game->start($session);
        $this->addFlash('notice', 'Spelet har startat!');
        return $this->redirectToRoute('game_play');
    }

    #[Route('/game/doc', name: 'game_doc')]
    public function doc(): Response
    {
        // Visa instruktioner/dokumentation
        return $this->render('game/doc.html.twig');
    }

    #[Route('/game/play', name: 'game_play')]
    public function play(SessionInterface $session): Response
    {
        $player = $session->get('player');
        $bank = $session->get('bank');
        $showBank = $session->get('showBank', false);

        $playerCards = ($player instanceof CardHand) ? $player->getCards() : [];
        $bankCards = ($showBank && $bank instanceof CardHand) ? $bank->getCards() : [];

        return $this->render('game/play.html.twig', [
            'player' => $playerCards,
            'bank' => $bankCards,
            'status' => $session->get('status', ''),
            'player_sum' => $session->get('player_sum', 0),
            'bank_sum' => $showBank ? $session->get('bank_sum', 0) : null,
            'scoreboard' => $session->get('scoreboard', []),
        ]);
    }

    #[Route('/game/draw', name: 'game_draw')]
    public function draw(SessionInterface $session, GameTwentyOne $game): Response
    {
        // Spelaren drar ett kort, eventuellt varningsmeddelande returneras
        $message = $game->draw($session);

        if (!empty($message)) {
            $this->addFlash('warning', $message);
        }

        return $this->redirectToRoute('game_play');
    }

    #[Route('/game/stay', name: 'game_stay')]
    public function stay(SessionInterface $session, GameTwentyOne $game): Response
    {
        // stay() returnerar enbart ett meddelande (sträng)
        $message = $game->stay($session);

        if (!empty($message)) {
            $this->addFlash('warning', $message);
        }

        return $this->redirectToRoute('game_play');
    }

    #[Route('/game/reset', name: 'game_reset')]
    public function reset(SessionInterface $session, GameTwentyOne $game): Response
    {
        // Återställ spelet genom att rensa session
        $game->reset($session);
        $this->addFlash('notice', 'Spelet har återställts.');
        return $this->redirectToRoute('game_index');
    }
}
