<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Kontrollerklass som visar ett "lyckonummer" med meddelande och bild
class LuckyControllerTwig extends AbstractController
{
    // Route: /lucky - Användare besöker denna URL för att se sitt lyckonummer
    #[Route("/lucky", name: "lucky")]
    public function number(): Response
    {
        // Slumpa ett heltal mellan 0 och 100
        $number = random_int(0, 100);

        // Hämta ett lyckomeddelande baserat på det slumpade numret
        $luckyMessage = $this->getLuckyMessage($number);

        // Lista med bilder (relativa sökvägar till byggda asset-filer)
        $images = [
            'build/images/klover.jpg',
            'build/images/klovdjur-07.jpg',
            'build/images/lucky.jpg',
        ];

        // Välj en slumpmässig bild från listan
        $randomImage = $images[array_rand($images)];

        // Skapa en array med data som skickas till Twig-templaten
        $data = [
            'number' => $number,         // Det slumpade numret
            'message' => $luckyMessage, // Meddelande baserat på numret
            'image' => $randomImage     // Den slumpmässiga bilden
        ];

        // Rendera Twig-mallen 'lucky_number.html.twig' med datan
        return $this->render('lucky_number.html.twig', $data);
    }

    // Privat metod som returnerar ett meddelande beroende på värdet av numret
    private function getLuckyMessage(int $number): string
    {
        if ($number < 20) {
            return 'Stora saker väntar på dig.';
        } elseif ($number < 50) {
            return 'Du är på rätt väg!';
        } else {
            return 'Framgång är nära!';
        }
    }
}
