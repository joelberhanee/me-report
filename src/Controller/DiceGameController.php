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
    // Route för att visa startsidan för tärningsspelet "Pig"
    #[Route("/game/pig", name: "pig_start")]
    public function home(): Response
    {
        // Renderar en Twig-template som är spelets startsida
        return $this->render('pig/home.html.twig');
    }

    // Test-route för att kasta en tärning och visa resultatet
    #[Route("/game/pig/test/roll", name: "test_roll_dice")]
    public function testRollDice(): Response
    {
        $die = new Dice();  // Skapar en tärning
        $data = [
            "dice" => $die->roll(),          // Slår tärningen och får ett värde
            "diceString" => $die->getAsString(),  // Hämtar en strängrepresentation av tärningen
        ];

        // Renderar template med tärningsresultatet
        return $this->render('pig/test/roll.html.twig', $data);
    }

    // Test-route för att kasta flera tärningar och visa alla resultat
    #[Route("/game/pig/test/roll/{num<\d+>}", name: "test_roll_num_dices")]
    public function testRollDices(int $num): Response
    {
        if ($num > 99) {
            // Säkerhetskontroll: max 99 tärningar
            throw new \Exception("Can not roll more than 99 dices!");
        }

        $diceRoll = [];
        // Loop som kastar $num tärningar av typen DiceGraphic (grafisk variant)
        for ($i = 1; $i <= $num; $i++) {
            $die = new DiceGraphic();
            $die->roll();
            $diceRoll[] = $die->getAsString(); // Samlar resultatet som sträng
        }

        $data = [
            "num_dices" => count($diceRoll),
            "diceRoll" => $diceRoll,
        ];

        // Renderar resultatet i en template
        return $this->render('pig/test/roll_many.html.twig', $data);
    }

    // Test-route för att skapa en hand med tärningar, blanda mellan grafisk och vanlig tärning
    #[Route("/game/pig/test/dicehand/{num<\d+>}", name: "test_dicehand")]
    public function testDiceHand(int $num): Response
    {
        if ($num > 99) {
            throw new \Exception("Can not roll more than 99 dices!");
        }

        $hand = new DiceHand();
        // Lägg till tärningar i handen, varannan DiceGraphic och varannan Dice
        for ($i = 1; $i <= $num; $i++) {
            if ($i % 2 === 1) {
                $hand->add(new DiceGraphic());
            } else {
                $hand->add(new Dice());
            }
        }

        $hand->roll(); // Kasta alla tärningar i handen

        $data = [
            "num_dices" => $hand->getNumberDices(),
            "diceRoll" => $hand->getString(), // Hämtar strängrepresentation av alla tärningar i handen
        ];

        return $this->render('pig/test/dicehand.html.twig', $data);
    }

    // GET-route för att visa sidan där spelaren initierar spelet (t.ex. väljer antal tärningar)
    #[Route("/game/pig/init", name: "pig_init_get", methods: ['GET'])]
    public function init(): Response
    {
        return $this->render('pig/init.html.twig');
    }

    // POST-route som tar emot formulärdata från init-sidan och startar spelet
    #[Route("/game/pig/init", name: "pig_init_post", methods: ['POST'])]
    public function initCallback(Request $request, SessionInterface $session): Response
    {
        $numDice = $request->request->get('num_dices');  // Hämtar antal tärningar från formuläret

        $hand = new DiceHand();
        // Skapa en hand med vald mängd DiceGraphic-tärningar
        for ($i = 1; $i <= $numDice; $i++) {
            $hand->add(new DiceGraphic());
        }
        $hand->roll(); // Kasta tärningarna en gång

        // Spara spelets startdata i sessionen
        $session->set("pig_dicehand", $hand);
        $session->set("pig_dices", $numDice);
        $session->set("pig_round", 0);  // Poäng för nuvarande runda
        $session->set("pig_total", 0);  // Totalpoäng

        // Skicka spelaren vidare till själva spelets sida
        return $this->redirectToRoute('pig_play');
    }

    // GET-route som visar spelets status (antal tärningar, poäng etc)
    #[Route("/game/pig/play", name: "pig_play", methods: ['GET'])]
    public function play(SessionInterface $session): Response
    {
        // Hämta tärningshand från sessionen, skapa ny om den inte finns
        $dicehand = $session->get("pig_dicehand");
        if (!$dicehand instanceof DiceHand) {
            $dicehand = new DiceHand();
            $session->set("pig_dicehand", $dicehand);
        }

        // Förbered data för template: antal tärningar, poäng i runda och totalt samt tärningarnas värden som sträng
        $data = [
            "pigDices" => $session->get("pig_dices"),
            "pigRound" => $session->get("pig_round"),
            "pigTotal" => $session->get("pig_total"),
            "diceValues" => $dicehand->getString()
        ];

        return $this->render('pig/play.html.twig', $data);
    }

    // POST-route som utför ett tärningskast i spelet (rullar tärningarna igen)
    #[Route("/game/pig/roll", name: "pig_roll", methods: ['POST'])]
    public function roll(SessionInterface $session): Response
    {
        $hand = $session->get("pig_dicehand");

        // Om handen inte finns i sessionen, skapa ny och spara
        if (!$hand instanceof DiceHand) {
            $hand = new DiceHand();
            $session->set("pig_dicehand", $hand);
        }

        $hand->roll(); // Kasta tärningarna

        $roundTotal = $session->get("pig_round"); // Poäng hittills i rundan
        $round = 0; // Poäng som samlas i detta kast
        $values = $hand->getValues(); // Hämtar värden för varje tärning i handen

        // Loopa igenom tärningsvärdena
        foreach ($values as $value) {
            if ($value === 1) {
                // Om någon tärning visar 1, förlorar spelaren rundans poäng
                $this->addFlash(
                    'warning',
                    'You got a 1 and you lost the round points!'
                );
                $round = 0;          // Runda poängen sätts till 0
                $roundTotal = 0;     // Poängen för rundan i sessionen också 0
                break;               // Sluta kontrollera fler tärningar
            }
            $round += $value; // Lägg till värdet till rundans poäng
        }

        // Uppdatera sessionens poäng för rundan
        $session->set("pig_round", $roundTotal + $round);

        // Skicka tillbaka användaren till spelets sida (GET /play)
        return $this->redirectToRoute('pig_play');
    }

    // POST-route som sparar poängen från rundan till totalpoängen
    #[Route("/game/pig/save", name: "pig_save", methods: ['POST'])]
    public function save(SessionInterface $session): Response
    {
        $roundTotal = $session->get("pig_round");
        $gameTotal = $session->get("pig_total");

        // Nollställ runda-poängen och lägg till dem till totalpoängen
        $session->set("pig_round", 0);
        $session->set("pig_total", $roundTotal + $gameTotal);

        $this->addFlash(
            'notice',
            'Your round was saved to the total!'
        );

        // Återvänd till spelets sida för att visa uppdaterad poäng
        return $this->redirectToRoute('pig_play');
    }
}
