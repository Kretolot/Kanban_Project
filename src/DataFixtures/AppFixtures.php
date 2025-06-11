<?php

namespace App\DataFixtures;

use App\Entity\Board;
use App\Entity\Col;
use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Usunięto domyślne tworzenie tablicy i kolumn.
        // Będziesz dodawać tablice za pomocą interfejsu użytkownika.
        // Jeśli chcesz mieć jakąś początkową tablicę, dodaj ją tutaj ręcznie
        // lub stwórz ją przez UI po pierwszym uruchomieniu.

        // Przykładowe dodanie jednej tablicy i kolumn (opcjonalne, tylko jeśli chcesz mieć coś na start)
        /*
        $board = new Board();
        $board->setName('Moja Pierwsza Tablica');
        $manager->persist($board);

        $defaultColNames = ['Do zrobienia', 'W trakcie', 'Ukończone'];
        foreach ($defaultColNames as $position => $colName) {
            $col = new Col();
            $col->setName($colName);
            $col->setPosition($position);
            $col->setBoard($board);
            $manager->persist($col);
        }

        // Przykładowe zadania (uważaj, aby przypisać je do istniejącej kolumny i tablicy)
        // Musisz upewnić się, że $colObjects[0] itp. istnieją i są odpowiednimi kolumnami
        // Jeśli będziesz tworzyć tablice dynamicznie, lepiej dodawać zadania przez UI.
        // Poniższy kod jest tylko przykładem, może wymagać dostosowania.
        // $colObjects = $board->getCols()->toArray(); // Pobierz kolumny, jeśli istnieją w tej tablicy

        // $tasks = [
        //     ['title' => 'Przykładowe zadanie 1', 'description' => 'Opis zadania 1', 'colIndex' => 0],
        //     ['title' => 'Przykładowe zadanie 2', 'description' => 'Opis zadania 2', 'colIndex' => 1],
        // ];

        // foreach ($tasks as $index => $taskData) {
        //     if (isset($colObjects[$taskData['colIndex']])) {
        //         $task = new Task();
        //         $task->setTitle($taskData['title']);
        //         $task->setDescription($taskData['description']);
        //         $task->setCol($colObjects[$taskData['colIndex']]);
        //         $task->setPosition($index);
        //         $manager->persist($task);
        //     }
        // }
        */

        $manager->flush();
    }
}