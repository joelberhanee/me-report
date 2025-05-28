<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Game\GameProj;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProjController extends AbstractController
{
    /**
     * Visar startsidan med formulär för att ange namn och saldo.
     * Fyller i tidigare inskickade värden om de finns i sessionen.
     */
    #[Route('/proj', name: 'proj_index')]
    public function index(Request $request): Response
    {
        return $this->render('proj/index.html.twig', [
            'old_name' => $request->getSession()->get('form_name', ''),
            'old_balance' => $request->getSession()->get('form_balance', ''),
        ]);
    }

    /**
     * Visar en "about" sida med information om spelet/projektet.
     */
    #[Route('/proj/about', name: 'proj_about')]
    public function doc(): Response
    {
        return $this->render('proj/about.html.twig');
    }

    /**
     * Tar emot POST från formulär med namn och saldo för att starta spelet.
     * Validerar att namn finns och att saldo är större än 0.
     * Sparar data i session och rensar tidigare speldata.
     * Redirectar till val av antal händer.
     */
    #[Route('/proj/start', name: 'proj_start', methods: ['POST'])]
    public function start(SessionInterface $session, Request $request): Response
    {
        $name = trim($request->request->get('name', ''));
        $balance = (int) $request->request->get('balance', 0);

        // Sparar inmatade värden för att kunna visa dem igen vid fel
        $session->set('form_name', $name);
        $session->set('form_balance', $balance);

        // Kontrollera giltighet på inmatning
        if ($name === '' || $balance <= 0) {
            $this->addFlash('warning', 'Ange ett giltigt namn och saldo.');
            return $this->redirectToRoute('proj_index');
        }

        // Rensa inmatade värden som nu är godkända
        $session->remove('form_name');
        $session->remove('form_balance');

        // Spara spelarens namn och saldo i sessionen
        $session->set('player_name', $name);
        $session->set('player_balance', $balance);

        // Rensa all eventuell tidigare speldata för att börja om
        $session->remove('deck');
        $session->remove('player_hands');
        $session->remove('bank');
        $session->remove('status');
        $session->remove('showBank');
        $session->remove('player_sums');
        $session->remove('bank_sum');
        $session->remove('bet');
        $session->remove('active_hand_index');
        $session->remove('scoreboard');
        $session->remove('hands');

        // Skicka vidare till att välja antal händer
        return $this->redirectToRoute('proj_choose_hands');
    }

    /**
     * Visar vy för att välja antal händer att spela (1-3).
     * Hämtar tidigare valt antal händer från session för förifyllnad.
     */
    #[Route('/choose-hands', name: 'proj_choose_hands', methods: ['GET'])]
    public function chooseHands(SessionInterface $session): Response
    {
        $oldHands = $session->get('hands', 1);

        return $this->render('proj/choose_hands.html.twig', [
            'old_hands' => $oldHands,
            'player_name' => $session->get('player_name', ''),
            'player_balance' => $session->get('player_balance', 0),
        ]);
    }

    /**
     * Tar emot POST med valt antal händer, validerar att det är mellan 1 och 3.
     * Sparar i session och redirectar till insats-sidan.
     */
    #[Route('/set-hands', name: 'proj_set_hands', methods: ['POST'])]
    public function setHands(Request $request, SessionInterface $session): RedirectResponse
    {
        $hands = (int) $request->request->get('hands', 1);

        if ($hands < 1 || $hands > 3) {
            $this->addFlash('error', 'Du måste välja mellan 1 och 3 händer.');
            return $this->redirectToRoute('proj_choose_hands');
        }

        $session->set('hands', $hands);
        return $this->redirectToRoute('proj_bet');
    }

    /**
     * Visar insatsformulär där spelaren kan lägga sin satsning per hand.
     * Kollar att det finns ett giltigt saldo och namn i session.
     * Om inte, skickas spelaren tillbaka till startsidan.
     */
    #[Route('/proj/bet', name: 'proj_bet')]
    public function bet(SessionInterface $session): Response
    {
        $balance = $session->get('player_balance', 0);
        $name = $session->get('player_name', '');

        if ($balance <= 0 || $name === '') {
            $this->addFlash('warning', 'Speldata saknas eller saldo är noll. Börja om.');
            return $this->redirectToRoute('proj_index');
        }

        return $this->render('proj/bet.html.twig', [
            'balance' => $balance,
            'player_name' => $name,
            'old_bet' => $session->get('form_bet', ''),
            'hands' => $session->get('hands', 1),
        ]);
    }

    /**
     * Tar emot insats från formuläret och validerar den.
     * Kollar att insatsen per hand gånger antal händer inte överstiger spelarens saldo.
     * Rensar gammal speldata och startar nytt spel med antal händer och insats.
     * Uppdaterar spelarens saldo med summan av satsningar.
     */
    #[Route('/proj/placebet', name: 'proj_placebet', methods: ['POST'])]
    public function placeBet(SessionInterface $session, GameProj $game, Request $request): Response
    {
        $bet = (int) $request->request->get('bet', 0);
        $balance = $session->get('player_balance', 0);
        $hands = $session->get('hands', 1);

        // Spara insatsen för att kunna visa den vid eventuella fel
        $session->set('form_bet', $bet);

        // Validering av insats
        if ($bet <= 0) {
            $this->addFlash('warning', 'Ange en giltig satsning större än 0.');
            return $this->redirectToRoute('proj_bet');
        }

        // Validera antal händer igen för säkerhet
        if ($hands < 1 || $hands > 3) {
            $this->addFlash('warning', 'Felaktigt antal händer. Välj mellan 1 och 3.');
            return $this->redirectToRoute('proj_choose_hands');
        }

        // Kontrollera att spelaren har tillräckligt med saldo
        if ($bet * $hands > $balance) {
            $this->addFlash('warning', sprintf(
                'Du har inte tillräckligt saldo för att spela %d händer med %d kr satsning per hand.',
                $hands,
                $bet
            ));
            return $this->redirectToRoute('proj_bet');
        }

        // Rensa eventuell gammal speldata innan nytt spel startar
        $session->remove('deck');
        $session->remove('player_hands');
        $session->remove('bank');
        $session->remove('status');
        $session->remove('showBank');
        $session->remove('player_sums');
        $session->remove('bank_sum');
        $session->remove('active_hand_index');
        $session->remove('scoreboard');

        // Uppdatera saldo efter satsningar
        $session->set('bet', $bet);
        $session->set('player_balance', $balance - $bet * $hands);

        // Starta spelet med antal händer via GameProj-klassen
        $game->start($session, $hands);

        // Skicka vidare till spelsidan
        return $this->redirectToRoute('proj_play');
    }

    /**
     * Visar själva spelsidan med spelarens händer, bankens kort och status.
     * Hämtar data från sessionen och förbereder för visning i Twig.
     */
    #[Route('/proj/play', name: 'proj_play')]
    public function play(SessionInterface $session): Response
    {
        $playerHandsObjects = $session->get('player_hands', []);
        $playerHands = [];

        // Extrahera kort från varje hand för att skicka till vyn
        foreach ($playerHandsObjects as $hand) {
            $playerHands[] = $hand->getCards();
        }

        $bank = $session->get('bank');
        $bankCards = $bank ? $bank->getCards() : [];

        return $this->render('proj/play.html.twig', [
            'player_hands' => $playerHands,
            'active_hand_index' => $session->get('active_hand_index', 0),
            'bank' => $bankCards,
            'status' => $session->get('status', ''),
            'showBank' => $session->get('showBank', false),
            'player_sums' => $session->get('player_sums', []),
            'bank_sum' => $session->get('bank_sum', 0),
            'balance' => $session->get('player_balance', 0),
            'bet' => $session->get('bet', 0),
            'scoreboard' => $session->get('scoreboard', [
                'playerWins' => 0,
                'bankWins' => 0,
                'draws' => 0,
            ]),
            'player_name' => $session->get('player_name', ''),
            'hands' => $session->get('hands', 1),
        ]);
    }

    /**
     * Hanterar dragning av kort på den aktiva handen.
     * Använder GameProj för att dra kort, få eventuella meddelanden.
     * Visar meddelande som flash om något speciellt händer (t.ex. bust).
     * Redirectar tillbaka till spelsidan.
     */
    #[Route('/proj/draw', name: 'proj_draw')]
    public function draw(SessionInterface $session, GameProj $game): Response
    {
        $message = $game->draw($session);

        if (!empty($message)) {
            $this->addFlash('warning', $message);
        }

        return $this->redirectToRoute('proj_play');
    }

    /**
     * Hanterar att spelaren väljer att stanna på aktiv hand.
     * Använder GameProj för att gå vidare i spelet.
     * Visar eventuella meddelanden via flash.
     * Redirectar tillbaka till spelsidan.
     */
    #[Route('/proj/stay', name: 'proj_stay')]
    public function stay(SessionInterface $session, GameProj $game): Response
    {
        $message = $game->stay($session);

        if (!empty($message)) {
            $this->addFlash('warning', $message);
        }

        return $this->redirectToRoute('proj_play');
    }

    /**
     * Rensar hela sessionen och skickar tillbaka till startsidan.
     * Används för att börja om spelet helt.
     */
    #[Route('/proj/reset', name: 'proj_reset', methods: ['GET'])]
    public function reset(SessionInterface $session): Response
    {
        $session->clear();
        return $this->redirectToRoute('proj_index');
    }
}
