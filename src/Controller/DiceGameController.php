<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Dice\Dice;
use App\Dice\DiceGraphic;
use App\Dice\DiceHand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiceGameController extends AbstractController
{
    /**
     * Startsida för Pig-spelet. Innehåller info och startlänk.
     */
    #[Route("/game/pig", name: "pig_start")]
    public function home(): Response
    {
        return $this->render('pig/home.html.twig');
    }

    /**
     * Test-route: Slår en enkel standardtärning (1–6) och visar resultatet.
     */
    #[Route("/game/pig/test/roll", name: "test_roll_dice")]
    public function testRollDice(): Response
    {
        $die = new Dice();

        $data = [
            "dice" => $die->roll(),              // Tärningskast, numeriskt värde
            "diceString" => $die->getAsString(), // Visuell representation (ex: "⚄")
        ];

        return $this->render('pig/test/roll.html.twig', $data);
    }

    /**
     * Test-route: Slår ett valfritt antal grafiska tärningar och visar dem.
     * Begränsad till max 99 tärningar.
     */
    #[Route("/game/pig/test/roll/{num<\d+>}", name: "test_roll_num_dices")]
    public function testRollDices(int $num): Response
    {
        if ($num > 99) {
            throw new \Exception("Can not roll more than 99 dices!");
        }

        $diceRoll = [];

        // Skapa och slå varje grafisk tärning
        for ($i = 1; $i <= $num; $i++) {
            $die = new DiceGraphic();
            $die->roll();
            $diceRoll[] = $die->getAsString(); // Lägg till den grafiska representationen
        }

        $data = [
            "num_dices" => count($diceRoll),
            "diceRoll" => $diceRoll,
        ];

        return $this->render('pig/test/roll_many.html.twig', $data);
    }

    /**
     * Test-route: Skapar ett DiceHand-objekt med blandade tärningar (grafiska och standard)
     * och visar tärningsresultaten.
     */
    #[Route("/game/pig/test/dicehand/{num<\d+>}", name: "test_dicehand")]
    public function testDiceHand(int $num): Response
    {
        if ($num > 99) {
            throw new \Exception("Can not roll more than 99 dices!");
        }

        $hand = new DiceHand();

        // Lägg till tärningar växelvis: grafisk (ojämnt index), vanlig (jämnt index)
        for ($i = 1; $i <= $num; $i++) {
            $hand->add($i % 2 === 1 ? new DiceGraphic() : new Dice());
        }

        $hand->roll();

        $data = [
            "num_dices" => $hand->getNumberDices(),
            "diceRoll" => $hand->getString(),
        ];

        return $this->render('pig/test/dicehand.html.twig', $data);
    }

    /**
     * GET: Visar ett formulär där spelaren kan välja antal tärningar inför spelets start.
     */
    #[Route("/game/pig/init", name: "pig_init_get", methods: ['GET'])]
    public function init(): Response
    {
        return $this->render('pig/init.html.twig');
    }

    /**
     * POST: Initierar spelet Pig med det antal tärningar som användaren valt.
     * Skapar och sparar DiceHand och speltillstånd i sessionen.
     */
    #[Route("/game/pig/init", name: "pig_init_post", methods: ['POST'])]
    public function initCallback(Request $request, SessionInterface $session): Response
    {
        $numDice = $request->request->get('num_dices');

        $hand = new DiceHand();

        // Lägg till det valda antalet grafiska tärningar
        for ($i = 1; $i <= $numDice; $i++) {
            $hand->add(new DiceGraphic());
        }

        $hand->roll(); // Första kastet

        // Initiera sessionsdata för spelet
        $session->set("pig_dicehand", $hand);
        $session->set("pig_dices", $numDice);
        $session->set("pig_round", 0); // Rundpoäng
        $session->set("pig_total", 0); // Totalpoäng

        return $this->redirectToRoute('pig_play');
    }

    /**
     * GET: Visar spelvyn med aktuell DiceHand, rundpoäng och totalpoäng.
     */
    #[Route("/game/pig/play", name: "pig_play", methods: ['GET'])]
    public function play(SessionInterface $session): Response
    {
        $dicehand = $session->get("pig_dicehand");

        // Om DiceHand saknas (t.ex. om session gått ut), skapa ny
        if (!$dicehand instanceof DiceHand) {
            $dicehand = new DiceHand();
            $session->set("pig_dicehand", $dicehand);
        }

        $data = [
            "pigDices" => $session->get("pig_dices"),
            "pigRound" => $session->get("pig_round"),
            "pigTotal" => $session->get("pig_total"),
            "diceValues" => $dicehand->getString()
        ];

        return $this->render('pig/play.html.twig', $data);
    }

    /**
     * POST: Slår om alla tärningar och uppdaterar rundpoängen.
     * Om någon tärning visar 1, förloras alla rundpoäng.
     */
    #[Route("/game/pig/roll", name: "pig_roll", methods: ['POST'])]
    public function roll(SessionInterface $session): Response
    {
        $hand = $session->get("pig_dicehand");

        if (!$hand instanceof DiceHand) {
            $hand = new DiceHand();
            $session->set("pig_dicehand", $hand);
        }

        $hand->roll();

        $roundTotal = $session->get("pig_round", 0); // Tidigare rundpoäng
        $round = 0;
        $values = $hand->getValues();

        foreach ($values as $value) {
            if ($value === 1) {
                // En etta → hela rundan förlorad
                $this->addFlash('warning', 'You got a 1 and you lost the round points!');
                $roundTotal = 0;
                $round = 0;
                break;
            }
            $round += $value;
        }

        // Spara uppdaterad rundpoäng
        $session->set("pig_round", $roundTotal + $round);

        return $this->redirectToRoute('pig_play');
    }

    /**
     * POST: Sparar aktuell rundpoäng till totalpoäng och nollställer rundan.
     */
    #[Route("/game/pig/save", name: "pig_save", methods: ['POST'])]
    public function save(SessionInterface $session): Response
    {
        $roundTotal = $session->get("pig_round", 0);
        $gameTotal = $session->get("pig_total", 0);

        // Spara rundpoängen till totalpoängen
        $session->set("pig_round", 0);
        $session->set("pig_total", $roundTotal + $gameTotal);

        $this->addFlash('notice', 'Your round was saved to the total!');

        return $this->redirectToRoute('pig_play');
    }
}
