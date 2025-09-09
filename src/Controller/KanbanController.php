<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\Col;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Repository\ColRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class KanbanController extends AbstractController
{
    private array $defaultColNames = ['Do zrobienia', 'W trakcie', 'Ukończone'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoardRepository $boardRepository,
        private ColRepository $colRepository,
        private TaskRepository $taskRepository
    ) {}

    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $boards = $this->boardRepository->findBy(['owner' => $user]);

        return $this->render('kanban/index.html.twig', [
            'boards' => $boards,
        ]);
    }

    #[Route('/board/{id}', name: 'kanban_board', methods: ['GET'])]
    public function board(Board $board): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($board->getOwner() !== $user) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tej tablicy.');
        }

        return $this->render('kanban/board.html.twig', [
            'board' => $board,
        ]);
    }

    #[Route('/board/add', name: 'kanban_add_board', methods: ['POST'])]
    public function addBoard(Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $boardName = $data['name'] ?? null;

        if (null === $boardName || empty(trim($boardName))) {
            return $this->handleValidationError(
                'Nazwa tablicy nie może być pusta.',
                $request
            );
        }

        if (mb_strlen($boardName) > 255) {
            return $this->handleValidationError(
                'Nazwa tablicy jest za długa (maks. 255 znaków).',
                $request
            );
        }

        $board = new Board();
        $board->setName($boardName);
        $board->setOwner($user);

        $errors = $validator->validate($board);

        if (count($errors) > 0) {
            return $this->handleValidationErrors($errors, $request);
        }

        try {
            $this->entityManager->beginTransaction();
            
            $this->entityManager->persist($board);
            $this->entityManager->flush();

            // Create default columns in bulk
            foreach ($this->defaultColNames as $position => $colName) {
                $col = new Col();
                $col->setName($colName);
                $col->setPosition($position);
                $col->setBoard($board);
                $this->entityManager->persist($col);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Nowa tablica "' . $boardName . '" została dodana z domyślnymi listami!',
                    'redirect' => $this->generateUrl('app_home')
                ], JsonResponse::HTTP_CREATED);
            }

            $this->addFlash('success', 'Nowa tablica "' . $boardName . '" została dodana z domyślnymi listami!');
            return $this->redirectToRoute('app_home');

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            return $this->handleError(
                'Wystąpił błąd podczas tworzenia tablicy.',
                $request
            );
        }
    }

    #[Route('/board/{id}', name: 'kanban_delete_board', methods: ['DELETE'])]
    public function deleteBoard(Board $board): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($board->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Nie masz uprawnień do usunięcia tej tablicy.'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->remove($board);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->addFlash('success', 'Tablica "' . $board->getName() . '" została pomyślnie usunięta.');
            return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('app_home')], JsonResponse::HTTP_OK);
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            return new JsonResponse(['error' => 'Wystąpił błąd podczas usuwania tablicy: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

<<<<<<< HEAD
    // ==================== COLUMN MANAGEMENT ====================
=======
    // ==================== NOWE METODY DLA ZARZĄDZANIA KOLUMNAMI ====================
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1

    #[Route('/board/{id}/column/add', name: 'kanban_add_column', methods: ['POST'])]
    public function addColumn(Board $board, Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($board->getOwner() !== $user) {
<<<<<<< HEAD
            return $this->handleAuthorizationError($request);
=======
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Nie masz uprawnień do dodania kolumny do tej tablicy.']], JsonResponse::HTTP_FORBIDDEN);
            }
            throw $this->createAccessDeniedException('Nie masz uprawnień do dodania kolumny do tej tablicy.');
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
        }

        $data = json_decode($request->getContent(), true);
        $colName = $data['name'] ?? null;

        if (null === $colName || empty(trim($colName))) {
<<<<<<< HEAD
            return $this->handleValidationError(
                'Nazwa kolumny nie może być pusta.',
                $request
            );
        }

        try {
            // Find max position
            $maxPosition = $this->colRepository->createQueryBuilder('c')
                ->select('MAX(c.position)')
                ->where('c.board = :board')
                ->setParameter('board', $board)
                ->getQuery()
                ->getSingleScalarResult();
            
            $col = new Col();
            $col->setName($colName);
            $col->setPosition(($maxPosition ?? -1) + 1);
            $col->setBoard($board);

            $errors = $validator->validate($col);

            if (count($errors) > 0) {
                return $this->handleValidationErrors($errors, $request);
            }

            $this->entityManager->persist($col);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Kolumna "' . $colName . '" została dodana!',
                    'redirect' => $this->generateUrl('kanban_board', ['id' => $board->getId()])
                ], JsonResponse::HTTP_CREATED);
            }

            $this->addFlash('success', 'Kolumna "' . $colName . '" została dodana!');
            return $this->redirectToRoute('kanban_board', ['id' => $board->getId()]);

        } catch (\Exception $e) {
            return $this->handleError('Wystąpił błąd podczas dodawania kolumny.', $request);
        }
=======
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Nazwa kolumny nie może być pusta.']], JsonResponse::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Nazwa kolumny nie może być pusta.');
            return $this->redirectToRoute('kanban_board', ['id' => $board->getId()]);
        }

        // Znajdź najwyższą pozycję i dodaj nową kolumnę na końcu
        $maxPosition = $this->colRepository->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->where('c.board = :board')
            ->setParameter('board', $board)
            ->getQuery()
            ->getSingleScalarResult();

        $col = new Col();
        $col->setName($colName);
        $col->setPosition(($maxPosition ?? -1) + 1);
        $col->setBoard($board);

        $errors = $validator->validate($col);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('kanban_board', ['id' => $board->getId()]);
            }
        }

        $this->entityManager->persist($col);
        $this->entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Kolumna "' . $colName . '" została dodana!',
                'redirect' => $this->generateUrl('kanban_board', ['id' => $board->getId()])
            ], JsonResponse::HTTP_CREATED);
        }

        $this->addFlash('success', 'Kolumna "' . $colName . '" została dodana!');
        return $this->redirectToRoute('kanban_board', ['id' => $board->getId()]);
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
    }

    #[Route('/column/{id}/move', name: 'kanban_move_column', methods: ['PATCH'])]
    public function moveColumn(Col $col, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($col->getBoard()->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Nie masz uprawnień do przeniesienia tej kolumny.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $newPosition = $data['position'] ?? null;

<<<<<<< HEAD
        if ($newPosition === null || !is_numeric($newPosition)) {
            return new JsonResponse(['error' => 'Nie podano prawidłowej pozycji.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->beginTransaction();
            
            $oldPosition = $col->getPosition();
            $board = $col->getBoard();

            // Get all columns for this board
            $columns = $this->colRepository->findBy(['board' => $board], ['position' => 'ASC']);

            // Reorganize positions
            if ($newPosition > $oldPosition) {
                // Moving right
                foreach ($columns as $column) {
                    $pos = $column->getPosition();
                    if ($pos > $oldPosition && $pos <= $newPosition) {
                        $column->setPosition($pos - 1);
                    }
                }
            } else {
                // Moving left
                foreach ($columns as $column) {
                    $pos = $column->getPosition();
                    if ($pos >= $newPosition && $pos < $oldPosition) {
                        $column->setPosition($pos + 1);
                    }
                }
            }

            $col->setPosition($newPosition);
            
            $this->entityManager->commit();

            return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            return new JsonResponse(['error' => 'Wystąpił błąd podczas przenoszenia kolumny.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
=======
        if ($newPosition === null) {
            return new JsonResponse(['error' => 'Nie podano nowej pozycji.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $oldPosition = $col->getPosition();
        $board = $col->getBoard();

        // Pobierz wszystkie kolumny tej tablicy
        $columns = $this->colRepository->findBy(['board' => $board], ['position' => 'ASC']);

        // Przeorganizuj pozycje
        if ($newPosition > $oldPosition) {
            // Przesuwanie w prawo
            foreach ($columns as $column) {
                $pos = $column->getPosition();
                if ($pos > $oldPosition && $pos <= $newPosition) {
                    $column->setPosition($pos - 1);
                }
            }
        } else {
            // Przesuwanie w lewo
            foreach ($columns as $column) {
                $pos = $column->getPosition();
                if ($pos >= $newPosition && $pos < $oldPosition) {
                    $column->setPosition($pos + 1);
                }
            }
        }

        $col->setPosition($newPosition);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_OK);
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
    }

    #[Route('/column/{id}', name: 'kanban_delete_column', methods: ['DELETE'])]
    public function deleteColumn(Col $col): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($col->getBoard()->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Nie masz uprawnień do usunięcia tej kolumny.'], JsonResponse::HTTP_FORBIDDEN);
        }

<<<<<<< HEAD
        $board = $col->getBoard();
        $boardId = $board->getId();

        // Check if it's not the last column
=======
        $boardId = $col->getBoard()->getId();
        $board = $col->getBoard();

        // Sprawdź czy to nie ostatnia kolumna
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
        $columnsCount = $this->colRepository->count(['board' => $board]);
        if ($columnsCount <= 1) {
            return new JsonResponse(['error' => 'Nie można usunąć ostatniej kolumny z tablicy.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
<<<<<<< HEAD
            $this->entityManager->beginTransaction();

            // Check if column has tasks
=======
            // Sprawdź czy kolumna ma zadania
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
            if ($col->getTasks()->count() > 0) {
                return new JsonResponse(['error' => 'Nie można usunąć kolumny zawierającej zadania. Przenieś najpierw zadania do innej kolumny.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $oldPosition = $col->getPosition();
            $this->entityManager->remove($col);

<<<<<<< HEAD
            // Update positions of remaining columns
=======
            // Zaktualizuj pozycje pozostałych kolumn
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
            $remainingColumns = $this->colRepository->createQueryBuilder('c')
                ->where('c.board = :board')
                ->andWhere('c.position > :position')
                ->setParameter('board', $board)
                ->setParameter('position', $oldPosition)
                ->getQuery()
                ->getResult();

            foreach ($remainingColumns as $column) {
                $column->setPosition($column->getPosition() - 1);
            }

<<<<<<< HEAD
            $this->entityManager->commit();

            $this->addFlash('success', 'Kolumna "' . $col->getName() . '" została pomyślnie usunięta.');
            return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('kanban_board', ['id' => $boardId])], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
=======
            $this->entityManager->flush();

            $this->addFlash('success', 'Kolumna "' . $col->getName() . '" została pomyślnie usunięta.');
            return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('kanban_board', ['id' => $boardId])], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania kolumny: ' . $e->getMessage());
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
            return new JsonResponse(['error' => 'Wystąpił błąd podczas usuwania kolumny: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/column/{id}/edit', name: 'kanban_edit_column', methods: ['POST'])]
    public function editColumn(Col $col, Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($col->getBoard()->getOwner() !== $user) {
<<<<<<< HEAD
            return $this->handleAuthorizationError($request);
        }

        if ($request->request->get('_method') !== 'PUT') {
            return $this->handleValidationError('Nieprawidłowa metoda żądania.', $request);
        }

        $name = $request->request->get('name');

        if (empty(trim($name))) {
            return $this->handleValidationError('Nazwa kolumny nie może być pusta.', $request);
        }

        try {
=======
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Nie masz uprawnień do edycji tej kolumny.']], JsonResponse::HTTP_FORBIDDEN);
            }
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tej kolumny.');
        }

        if ($request->request->get('_method') === 'PUT') {
            $name = $request->request->get('name');

            if (empty(trim($name))) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'errors' => ['Nazwa kolumny nie może być pusta.']], JsonResponse::HTTP_BAD_REQUEST);
                }
                $this->addFlash('error', 'Nazwa kolumny nie może być pusta.');
                return $this->redirectToRoute('kanban_board', ['id' => $col->getBoard()->getId()]);
            }

>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
            $col->setName($name);

            $errors = $validator->validate($col);

            if (count($errors) > 0) {
<<<<<<< HEAD
                return $this->handleValidationErrors($errors, $request);
=======
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
                } else {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    return $this->redirectToRoute('kanban_board', ['id' => $col->getBoard()->getId()]);
                }
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1
            }

            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Kolumna zaktualizowana pomyślnie!',
                    'redirect' => $this->generateUrl('kanban_board', ['id' => $col->getBoard()->getId()])
                ], JsonResponse::HTTP_OK);
            }
<<<<<<< HEAD

            return $this->redirectToRoute('kanban_board', ['id' => $col->getBoard()->getId()]);

        } catch (\Exception $e) {
            return $this->handleError('Wystąpił błąd podczas edycji kolumny.', $request);
        }
    }

    // ==================== TASK MANAGEMENT ====================
=======
            return $this->redirectToRoute('kanban_board', ['id' => $col->getBoard()->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'errors' => ['Nieprawidłowa metoda żądania.']], JsonResponse::HTTP_METHOD_NOT_ALLOWED);
        }
        throw $this->createNotFoundException('Invalid request method');
    }

    // ==================== METODY DLA ZADAŃ (bez zmian) ====================
>>>>>>> 81ee01cdda288f732d8f58084e2ec3018e33cda1

    #[Route('/task/{id}/move', methods: ['PATCH'])]
    public function moveTask(Task $task, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($task->getCol()->getBoard()->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Nie masz uprawnień do przeniesienia tego zadania.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $newCol = $this->colRepository->find($data['colId']);

        if (!$newCol || $newCol->getBoard()->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Kolumna nie została znaleziona lub nie należy do Twoich tablic.'], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $task->setCol($newCol);

            if (isset($data['position'])) {
                $task->setPosition($data['position']);
            }

            $this->entityManager->flush();

            return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Wystąpił błąd podczas przenoszenia zadania.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/board/{id}/task/add', name: 'kanban_add_task', methods: ['POST'])]
    public function addTask(Board $board, Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($board->getOwner() !== $user) {
            return $this->handleAuthorizationError($request);
        }

        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $colId = $request->request->get('col_id');

        $col = $this->colRepository->find($colId);

        if (!$col || $col->getBoard() !== $board) {
            return $this->handleValidationError(
                'Kolumna nie została znaleziona lub nie należy do tej tablicy.',
                $request
            );
        }

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setCol($col);
        $task->setPosition($col->getTasks()->count());

        $errors = $validator->validate($task);

        if (count($errors) > 0) {
            return $this->handleValidationErrors($errors, $request);
        }

        try {
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Zadanie dodane pomyślnie!',
                    'redirect' => $this->generateUrl('kanban_board', ['id' => $board->getId()])
                ], JsonResponse::HTTP_CREATED);
            }

            return $this->redirectToRoute('kanban_board', ['id' => $board->getId()]);

        } catch (\Exception $e) {
            return $this->handleError('Wystąpił błąd podczas dodawania zadania.', $request);
        }
    }

    #[Route('/task/{id}/edit', name: 'kanban_edit_task', methods: ['POST'])]
    public function editTask(Task $task, Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($task->getCol()->getBoard()->getOwner() !== $user) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Nie masz uprawnień do edycji tego zadania.']], JsonResponse::HTTP_FORBIDDEN);
            }
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tego zadania.');
        }

        if ($request->request->get('_method') === 'PUT') {
            $title = $request->request->get('title');
            $description = $request->request->get('description');

            $task->setTitle($title);
            $task->setDescription($description);

            $errors = $validator->validate($task);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
                } else {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    return $this->redirectToRoute('kanban_board', ['id' => $task->getCol()->getBoard()->getId()]);
                }
            }

            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Zadanie zaktualizowane pomyślnie!',
                    'redirect' => $this->generateUrl('kanban_board', ['id' => $task->getCol()->getBoard()->getId()])
                ], JsonResponse::HTTP_OK);
            }
            return $this->redirectToRoute('kanban_board', ['id' => $task->getCol()->getBoard()->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'errors' => ['Nieprawidłowa metoda żądania.']], JsonResponse::HTTP_METHOD_NOT_ALLOWED);
        }
        throw $this->createNotFoundException('Invalid request method');
    }

    #[Route('/task/{id}', name: 'kanban_delete_task', methods: ['DELETE'])]
    public function deleteTask(Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($task->getCol()->getBoard()->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Nie masz uprawnień do usunięcia tego zadania.'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $boardId = $task->getCol()->getBoard()->getId();
            $this->entityManager->remove($task);
            $this->entityManager->flush();

            $this->addFlash('success', 'Zadanie zostało pomyślnie usunięte.');
            return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('kanban_board', ['id' => $boardId])], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania zadania: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Wystąpił błąd podczas usuwania zadania: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ==================== UTILITY METHODS ====================

    private function handleValidationError(string $message, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'errors' => [$message]], JsonResponse::HTTP_BAD_REQUEST);
        }
        $this->addFlash('error', $message);
        return $this->redirectToRoute('app_home');
    }

    private function handleValidationErrors($errors, Request $request): Response
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }
        return $this->redirectToRoute('app_home');
    }

    private function handleAuthorizationError(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'errors' => ['Nie masz uprawnień do wykonania tej operacji.']], JsonResponse::HTTP_FORBIDDEN);
        }
        throw $this->createAccessDeniedException('Nie masz uprawnień do wykonania tej operacji.');
    }

    private function handleError(string $message, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'errors' => [$message]], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->addFlash('error', $message);
        return $this->redirectToRoute('app_home');
    }
}