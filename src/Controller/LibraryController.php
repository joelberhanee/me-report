<?php

namespace App\Controller;

use App\Entity\Book; // Bok-entiteten som representerar en bok i databasen
use Doctrine\Persistence\ManagerRegistry; // För att få Doctrine Entity Manager
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Bascontroller från Symfony
use Symfony\Component\HttpFoundation\Request; // Hantering av HTTP-förfrågningar
use Symfony\Component\HttpFoundation\Response; // HTTP-respons
use Symfony\Component\Routing\Attribute\Route; // Routing-attribut

final class LibraryController extends AbstractController
{
    // Route för startsidan i biblioteket
    #[Route('/library', name: 'library')]
    public function index(): Response
    {
        // Renderar en enkel vy, troligen en välkomst- eller översiktssida
        return $this->render('library/index.html.twig', [
            'controller_name' => 'LibraryController',
        ]);
    }

    // Route för att skapa en ny bok (både GET och POST hanteras här)
    #[Route('/library/create', name: 'create_book')]
    public function createBook(
        Request $request,
        ManagerRegistry $doctrine
    ): Response {
        $entityManager = $doctrine->getManager(); // Hämtar entity manager för DB-åtgärder
        $book = new Book(); // Ny bok-instans

        if ($request->isMethod('POST')) {
            // Om formuläret skickas (POST), sätt egenskaper från formuläret
            $book->setTitle((string) $request->request->get('title', ''));
            $book->setIsbn((string) $request->request->get('isbn', ''));
            $book->setAuthor((string) $request->request->get('author', ''));
            $book->setImage((string) $request->request->get('image', ''));

            // Spara den nya boken i databasen
            $entityManager->persist($book);
            $entityManager->flush();

            // Omdirigera till sidan som visar alla böcker
            return $this->redirectToRoute('library_all');
        }

        // Visa formuläret för att skapa bok (GET)
        return $this->render('library/create.html.twig');
    }

    // Route för att visa alla böcker i biblioteket
    #[Route('/library/all', name: 'library_all')]
    public function allBooks(
        ManagerRegistry $doctrine
    ): Response {
        $entityManager = $doctrine->getManager();
        // Hämtar alla böcker från databasen via repository
        $books = $entityManager->getRepository(Book::class)->findAll();

        // Renderar vy med alla böcker
        return $this->render('library/all_books.html.twig', [
            'books' => $books,
        ]);
    }

    // Route för att visa detaljer om en specifik bok baserat på id
    #[Route('/library/{id}', name: 'book_details')]
    public function bookDetails(
        ManagerRegistry $doctrine,
        int $id
    ): Response {
        $entityManager = $doctrine->getManager();
        // Hämtar boken med angivet id
        $book = $entityManager->getRepository(Book::class)->find($id);

        // Om boken inte finns, visa 404-sida
        if (!$book) {
            throw $this->createNotFoundException('Boken finns inte');
        }

        // Rendera vy med bokdetaljer
        return $this->render('library/book_details.html.twig', [
            'book' => $book,
        ]);
    }

    // Route för att redigera en bok (GET visar formulär, POST sparar ändringar)
    #[Route('/library/edit/{id}', name: 'edit_book')]
    public function editBook(
        ManagerRegistry $doctrine,
        Request $request,
        int $id
    ): Response {
        $entityManager = $doctrine->getManager();
        // Hämta boken som ska redigeras
        $book = $entityManager->getRepository(Book::class)->find($id);

        // Om boken inte finns, visa 404
        if (!$book) {
            throw $this->createNotFoundException('Boken finns inte');
        }

        if ($request->isMethod('POST')) {
            // Spara ändringar från formuläret till boken
            $book->setTitle((string) $request->request->get('title', ''));
            $book->setIsbn((string) $request->request->get('isbn', ''));
            $book->setAuthor((string) $request->request->get('author', ''));
            $book->setImage((string) $request->request->get('image', ''));

            // Spara ändringarna i databasen
            $entityManager->flush();

            // Omdirigera till detaljsidan för boken
            return $this->redirectToRoute('book_details', ['id' => $book->getId()]);
        }

        // Visa formulär för redigering (GET)
        return $this->render('library/edit_book.html.twig', [
            'book' => $book,
        ]);
    }

    // Route för att radera en bok baserat på id
    #[Route('/library/delete/{id}', name: 'delete_book')]
    public function deleteBook(
        ManagerRegistry $doctrine,
        int $id
    ): Response {
        $entityManager = $doctrine->getManager();
        // Hämta boken som ska tas bort
        $book = $entityManager->getRepository(Book::class)->find($id);

        // Om boken inte finns, visa 404
        if (!$book) {
            throw $this->createNotFoundException('Boken finns inte.');
        }

        // Ta bort boken från databasen
        $entityManager->remove($book);
        $entityManager->flush();

        // Omdirigera till sidan med alla böcker
        return $this->redirectToRoute('library_all');
    }
}
