<?php

namespace App\Controller;

use App\Entity\Book;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LibraryController extends AbstractController
{
    #[Route('/library', name: 'library')]
    public function index(): Response
    {
        return $this->render('library/index.html.twig', [
            'controller_name' => 'LibraryController',
        ]);
    }

    #[Route('/library/create', name: 'create_book')]
    public function createBook(
        Request $request,
        ManagerRegistry $doctrine
    ): Response {
        $entityManager = $doctrine->getManager();
        $book = new Book();

        if ($request->isMethod('POST')) {
            $book->setTitle((string) $request->request->get('title', ''));
            $book->setIsbn((string) $request->request->get('isbn', ''));
            $book->setAuthor((string) $request->request->get('author', ''));
            $book->setImage((string) $request->request->get('image', ''));

            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('library_all');
        }

        return $this->render('library/create.html.twig');
    }

    #[Route('/library/all', name: 'library_all')]
    public function allBooks(
        ManagerRegistry $doctrine
    ): Response {
        $entityManager = $doctrine->getManager();
        $books = $entityManager->getRepository(Book::class)->findAll();

        return $this->render('library/all_books.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/library/{id}', name: 'book_details')]
    public function bookDetails(
        ManagerRegistry $doctrine,
        int $id
    ): Response {
        $entityManager = $doctrine->getManager();
        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            throw $this->createNotFoundException('Boken finns inte');
        }

        return $this->render('library/book_details.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/library/edit/{id}', name: 'edit_book')]
    public function editBook(
        ManagerRegistry $doctrine,
        Request $request,
        int $id
    ): Response {
        $entityManager = $doctrine->getManager();
        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            throw $this->createNotFoundException('Boken finns inte');
        }

        if ($request->isMethod('POST')) {
            $book->setTitle((string) $request->request->get('title', ''));
            $book->setIsbn((string) $request->request->get('isbn', ''));
            $book->setAuthor((string) $request->request->get('author', ''));
            $book->setImage((string) $request->request->get('image', ''));

            $entityManager->flush();

            return $this->redirectToRoute('book_details', ['id' => $book->getId()]);
        }

        return $this->render('library/edit_book.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/library/delete/{id}', name: 'delete_book')]
    public function deleteBook(
        ManagerRegistry $doctrine,
        int $id
    ): Response {
        $entityManager = $doctrine->getManager();
        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            throw $this->createNotFoundException('Boken finns inte.');
        }

        $entityManager->remove($book);
        $entityManager->flush();

        return $this->redirectToRoute('library_all');
    }
}
