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

    #[Route('/board/add', name: 'kanban_add_board', methods: ['POST'])]
    public function addBoard(Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $boardName = $data['name'] ?? null;

        if (null === $boardName || empty(trim($boardName))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Nazwa tablicy nie może być pusta.']], JsonResponse::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Nazwa tablicy nie może być pusta.');
            return $this->redirectToRoute('app_home');
        }

        if (mb_strlen($boardName) > 255) {
             if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Nazwa tablicy jest za długa (maks. 255 znaków).']], JsonResponse::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Nazwa tablicy jest za długa (maks. 255 znaków).');
            return $this->redirectToRoute('app_home');
        }

        $board = new Board();
        $board->setName($boardName);
        $board->setOwner($user);

        $errors = $validator->validate($board);

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
                return $this->redirectToRoute('app_home');
            }
        }

        $this->entityManager->persist($board);
        $this->entityManager->flush();

        foreach ($this->defaultColNames as $position => $colName) {
            $col = new Col();
            $col->setName($colName);
            $col->setPosition($position);
            $col->setBoard($board);
            $this->entityManager->persist($col);
        }

        $this->entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Nowa tablica "' . $boardName . '" została dodana z domyślnymi listami!',
                'redirect' => $this->generateUrl('app_home')
            ], JsonResponse::HTTP_CREATED);
        } else {
            $this->addFlash('success', 'Nowa tablica "' . $boardName . '" została dodana z domyślnymi listami!');
            return $this->redirectToRoute('app_home');
        }
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

    #[Route('/board/{id}', name: 'kanban_delete_board', methods: ['DELETE'])]
    public function deleteBoard(Board $board): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($board->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Nie masz uprawnień do usunięcia tej tablicy.'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->remove($board);
            $this->entityManager->flush();

            $this->addFlash('success', 'Tablica "' . $board->getName() . '" została pomyślnie usunięta.');
            return new JsonResponse(['status' => 'success', 'redirect' => $this->generateUrl('app_home')], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania tablicy: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Wystąpił błąd podczas usuwania tablicy: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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

        $task->setCol($newCol);

        if (isset($data['position'])) {
            $task->setPosition($data['position']);
        }

        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_OK);
    }

    #[Route('/board/{id}/task/add', name: 'kanban_add_task', methods: ['POST'])]
    public function addTask(Board $board, Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($board->getOwner() !== $user) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Nie masz uprawnień do dodania zadania do tej tablicy.']], JsonResponse::HTTP_FORBIDDEN);
            }
            throw $this->createAccessDeniedException('Nie masz uprawnień do dodania zadania do tej tablicy.');
        }

        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $colId = $request->request->get('col_id');

        $col = $this->colRepository->find($colId);

        if (!$col || $col->getBoard() !== $board) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'errors' => ['Kolumna nie została znaleziona lub nie należy do tej tablicy.']], JsonResponse::HTTP_NOT_FOUND);
            }
            throw $this->createNotFoundException('Kolumna nie została znaleziona lub nie należy do tej tablicy.');
        }

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setCol($col);
        $task->setPosition($col->getTasks()->count());

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
                return $this->redirectToRoute('kanban_board', ['id' => $board->getId()]);
            }
        }

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
}