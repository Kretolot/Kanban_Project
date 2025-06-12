<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security; // Ważne: dodaj tę linię

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_root_landing')] // Zmieniono nazwę trasy, aby była unikalna
    public function index(Security $security): Response // Dodano wstrzyknięcie Security
    {
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_home'); // Przekierowanie na nową trasę /home
        }

        return $this->redirectToRoute('app_register');
    }
}