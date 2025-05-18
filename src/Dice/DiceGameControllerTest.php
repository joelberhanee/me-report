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

class DiceGameControllerTest extends AbstractController
{
    /**
     * Start-/landningssidan för Pig-spelet.
     *
     * @return Response
     */
    #[Route("/game/pig", name: "pig_start")]
    public function home(): Response
    {
        return $this->render('pig/home.html.twig');
    }

    /**
     * Testar att rulla en enkel tärning.
     *
     * @return Response
     */
    #[Route("/game/pig/test/roll", name: "test_roll_dice")]
    public function testRollDice(): Response
    {
        $die = new Dice();

        $data = [
            "dice" => $die->roll(),
            "diceString" => $die->getAsString(),
        ];

        return $this->render('pig/test/roll.html.twig', $data);
    }

    /**
     * Testar att rulla ett antal grafiska tärningar.
     *
     * @param int $num Antal tärningar att rulla (max 99)
     * @return Response
     */
    #[Route("/game/pig/test/roll/{num<\d+>}", name: "test_roll_num_dices")]
    public function testRollDices(int $num): Response
    {
        if ($num > 99) {
            throw new \Exception("Can not roll more than 99 dices!");
        }

        $diceRoll = [];
        for ($i = 1; $i <= $num; $i++) {
            $die = new DiceGraphic();
            $die->roll();
            $diceRoll[] = $die->getAsString();
        }

        $data = [
            "num_dices" => count($diceRoll),
            "diceRoll" => $diceRoll,
        ];

        return $this->render('pig/test/roll_many.html.twig', $data);
    }

    /**
     * Testar en DiceHand med både vanliga och grafiska tärningar.
     *
     * @param int $num Antal tärningar (max 99)
     * @return Response
     */
    #[Route("/game/pig/test/dicehand/{num<\d+>}", name: "test_dicehand")]
    public function testDiceHand(int $num): Response
    {
        if ($num > 99) {
            throw new \Exception("Can not roll more than 99 dices!");
        }

        $hand = new DiceHand();

        // Lägg till alternerande Dice och DiceGraphic
        for ($i = 1; $i <= $num; $i++) {
            if ($i % 2 === 1) {
                $hand->add(new DiceGraphic());
            } else {
                $hand->add(new Dice());
            }
        }

        $hand->roll();

        $data = [
            "num_dices" => $hand->getNumberDices(),
            "diceRoll" => $hand->getString(),
        ];

        return $this->render('pig/test/dicehand.html.twig', $data);
    }

    /**
     * Visar initieringsformuläret för Pig-spelet.
     *
     * @return Response
     */
    #[Route("/game/pig/init", name: "pig_init_get", methods: ['GET'])]
    public function init(): Response
    {
        return $this->render('pig/init.html.twig');
    }

    /**
     * Tar emot formulärdata och initierar spel i sessionen.
     *
     * @param Request $request
     * @param SessionInterface $session
     * @return Response
     */
    #[Route("/game/pig/init", name: "pig_init_post", methods: ['POST'])]
    public function initCallback(Request $request, SessionInterface $session): Response
    {
        $numDice = $request->request->get('num_dices');

        $hand = new DiceHand();
        for ($i = 1; $i <= $numDice; $i++) {
            $hand->add(new DiceGraphic());
        }
        $hand->roll();

        // Initiera sessionsdata
        $session->set("pig_dicehand", $hand);
        $session->set("pig_dices", $numDice);
        $session->set("pig_round", 0);
        $session->set("pig_total", 0);

        return $this->redirectToRoute('pig_play');
    }

    /**
     * Visar spelets pågående status (antal poäng och senaste kast).
     *
     * @param SessionInterface $session
     * @return Response
     */
    #[Route("/game/pig/play", name: "pig_play", methods: ['GET'])]
    public function play(SessionInterface $session): Response
    {
        $dicehand = $session->get("pig_dicehand");

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
     * Rullar tärningarna i spelet och uppdaterar rundpoäng.
     *
     * @param SessionInterface $session
     * @return Response
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

        $roundTotal = $session->get("pig_round");
        $round = 0;
        $values = $hand->getValues();

        // Om någon tärning är 1: runda förlorad
        foreach ($values as $value) {
            if ($value === 1) {
                $this->addFlash(
                    'warning',
                    'You got a 1 and you lost the round points!'
                );
                $round = 0;
                $roundTotal = 0;
                break;
            }
            $round += $value;
        }

        $session->set("pig_round", $roundTotal + $round);

        return $this->redirectToRoute('pig_play');
    }

    /**
     * Sparar rundpoäng till totalpoäng och nollställer rundan.
     *
     * @param SessionInterface $session
     * @return Response
     */
    #[Route("/game/pig/save", name: "pig_save", methods: ['POST'])]
    public function save(SessionInterface $session): Response
    {
        $roundTotal = $session->get("pig_round");
        $gameTotal = $session->get("pig_total");

        $session->set("pig_round", 0);
        $session->set("pig_total", $roundTotal + $gameTotal);

        $this->addFlash(
            'notice',
            'Your round was saved to the total!'
        );

        return $this->redirectToRoute('pig_play');
    }
}
