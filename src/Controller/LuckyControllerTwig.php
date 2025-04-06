<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LuckyControllerTwig extends AbstractController
{
    #[Route("/lucky", name: "lucky")]
    public function number(): Response
    {
        $number = random_int(0, 100);
        $luckyMessage = $this->getLuckyMessage($number);

        // Lista med bilder
        $images = [
            'img/klover.jpg',
            'img/klovdjur-07.jpg',
            'img/lucky.jpg',
        ];

        // Slumpmässig bild
        $randomImage = $images[array_rand($images)];

        $data = [
            'number' => $number,
            'message' => $luckyMessage,
            'image' => $randomImage, // Skicka endast en slumpmässig bild
        ];

        return $this->render('lucky_number.html.twig', $data);
    }

    private function getLuckyMessage($number): string
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
