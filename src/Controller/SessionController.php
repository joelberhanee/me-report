<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class SessionController extends AbstractController
{
    #[Route('/session', name: 'session_view')]
    public function view(SessionInterface $session): Response
    {
        return $this->render('session/view.html.twig', [
            'session' => $session->all(),
        ]);
    }

    #[Route('/session/delete', name: 'session_delete')]
    public function delete(SessionInterface $session): Response
    {
        $session->clear();

        $this->addFlash('notice', 'Sessionen är raderad!');
        return $this->redirectToRoute('session_view');
    }

    #[Route('/session/restart', name: 'session_restart')]
    public function restart(SessionInterface $session): Response
    {
        $session->clear();

        $this->addFlash('notice', 'Spelet startades om!');
        return $this->redirectToRoute('card_index');
    }
}
