<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\Col;
use App\Entity\Task;
use App\Repository\BoardRepository;
use App\Repository\ColRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class KanbanController extends AbstractController
{
    private array $defaultColNames = ['Do zrobienia', 'W trakcie', 'Ukończone'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoardRepository $boardRepository,
        private ColRepository $colRepository,
        private TaskRepository $taskRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $boards = $this->boardRepository->findAll();
        return $this->render('kanban/index.html.twig', [
            'boards' => $boards,
        ]);
    }

    // NOWA FUNKCJONALNOŚĆ: DODAWANIE NOWEJ TABLICY
    // Ta trasa musi być przed trasą '/board/{id}'
    #[Route('/board/add', name: 'kanban_add_board', methods: ['POST'])]
    public function addBoard(Request $request): Response
    {
        $boardName = $request->request->get('name');

        if (!$boardName) {
            $this->addFlash('error', 'Nazwa tablicy nie może być pusta.');
            return $this->redirectToRoute('app_home');
        }

        $board = new Board();
        $board->setName($boardName);
        $this->entityManager->persist($board);
        $this->entityManager->flush(); // Flush now to get the board ID for cols

        foreach ($this->defaultColNames as $position => $colName) {
            $col = new Col();
            $col->setName($colName);
            $col->setPosition($position);
            $col->setBoard($board);
            $this->entityManager->persist($col);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Nowa tablica "' . $boardName . '" została dodana z domyślnymi listami!');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/board/{id}', name: 'kanban_board', methods: ['GET'])] // Dodano methods: ['GET']
    public function board(Board $board): Response
    {
        return $this->render('kanban/board.html.twig', [
            'board' => $board,
        ]);
    }

    #[Route('/board/{id}', name: 'kanban_delete_board', methods: ['DELETE'])]
    public function deleteBoard(Board $board): JsonResponse
    {
        $canDelete = true;
        foreach ($board->getCols() as $col) {
            // Sprawdzamy tylko kolumny, które NIE są "Ukończone"
            if ($col->getName() !== 'Ukończone' && $col->getTasks()->count() > 0) {
                $canDelete = false;
                break; // Znaleziono zadanie poza kolumną "Ukończone", więc nie można usunąć
            }
        }

        if (!$canDelete) {
            return new JsonResponse(['error' => 'Nie można usunąć tablicy, ponieważ zawiera zadania poza listą "Ukończone". Przenieś wszystkie zadania do listy "Ukończone" lub usuń je.'], 400);
        }

        // Jeśli wszystkie zadania są w kolumnie "Ukończone" (lub nie ma zadań w ogóle),
        // usuwamy zadania w kolumnie "Ukończone" przed usunięciem kolumn i tablicy.
        foreach ($board->getCols() as $col) {
            foreach ($col->getTasks() as $task) {
                $this->entityManager->remove($task);
            }
            $this->entityManager->remove($col); // Usuń kolumnę po usunięciu jej zadań
        }
        $this->entityManager->remove($board);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('app_home')]);
    }


    #[Route('/task/{id}/move', methods: ['PATCH'])]
    public function moveTask(Task $task, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newCol = $this->colRepository->find($data['colId']);

        if (!$newCol) {
            return new JsonResponse(['error' => 'Column not found'], 404);
        }

        $task->setCol($newCol);

        if (isset($data['position'])) {
            $task->setPosition($data['position']);
        }

        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/board/{id}/task/add', methods: ['POST'])]
    public function addTask(Board $board, Request $request): Response
    {
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $colId = $request->request->get('col_id');

        $col = $this->colRepository->find($colId);

        if (!$col || $col->getBoard() !== $board) {
            throw $this->createNotFoundException('Column not found');
        }

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setCol($col);
        $task->setPosition($col->getTasks()->count()); // Ustawia pozycję na końcu listy

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->redirectToRoute('kanban_board', ['id' => $board->getId()]);
    }

    #[Route('/task/{id}/edit', methods: ['POST'])] // Zmieniamy na POST, bo używamy _method=PUT
    public function editTask(Task $task, Request $request): Response
    {
        if ($request->request->get('_method') === 'PUT') {
            $title = $request->request->get('title');
            $description = $request->request->get('description');

            $task->setTitle($title);
            $task->setDescription($description);

            $this->entityManager->flush();

            return $this->redirectToRoute('kanban_board', ['id' => $task->getCol()->getBoard()->getId()]);
        }

        throw $this->createNotFoundException('Invalid request method');
    }

    #[Route('/task/{id}', methods: ['DELETE'])]
    public function deleteTask(Task $task): JsonResponse
    {
        $boardId = $task->getCol()->getBoard()->getId(); // Zachowaj ID tablicy do przekierowania
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('kanban_board', ['id' => $boardId])]);
    }
}