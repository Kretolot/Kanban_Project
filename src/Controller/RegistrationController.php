<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Psr\Log\LoggerInterface; // Do logowania błędów
use Symfony\Component\Mailer\Exception\TransportExceptionInterface; // Specyficzny wyjątek dla problemów z transportem maila
// Jeśli używasz SwiftMailer, potrzebujesz: use Swift_TransportException;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;
    private LoggerInterface $logger;

    public function __construct(EmailVerifier $emailVerifier, LoggerInterface $logger)
    {
        $this->emailVerifier = $emailVerifier;
        $this->logger = $logger;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Jeśli użytkownik jest już zalogowany, przekieruj go na stronę główną
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Obsługa przesłanego i prawidłowego formularza
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Haszowanie i ustawienie hasła użytkownika
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Zapisanie użytkownika do bazy danych
                $entityManager->persist($user);
                $entityManager->flush();

                // Wysyłka e-maila weryfikacyjnego
                $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('no-reply@kanbanapp.com', 'Kanban App'))
                        ->to($user->getEmail())
                        ->subject('Witaj w Kanban App!')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );

                // Dodanie wiadomości sukcesu i przekierowanie
                $this->addFlash('success', 'Twoje konto zostało zarejestrowane! Sprawdź pocztę e-mail, aby aktywować konto. Możesz się zalogować.');
                return $this->redirectToRoute('app_login');

            } catch (TransportExceptionInterface $e) {
                // Obsługa błędów związanych z wysyłką poczty e-mail
                $errorMessage = 'Wystąpił problem z wysyłką wiadomości e-mail. Sprawdź poprawność adresu lub spróbuj ponownie później.';
                // Spróbuj być bardziej precyzyjnym, jeśli wiadomość wyjątku na to pozwala
                if (str_contains($e->getMessage(), 'invalid recipient') || str_contains($e->getMessage(), 'rejected')) {
                    $errorMessage = 'Podany adres e-mail nie istnieje lub jest nieprawidłowy. Proszę podać poprawny adres.';
                }
                $this->logger->error('Błąd wysyłki emaila (TransportException): ' . $e->getMessage(), ['exception' => $e]);
                $this->addFlash('error', $errorMessage);
                
                // Renderuj formularz ponownie z błędem
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            } catch (\Exception $e) {
                // Obsługa wszelkich innych nieoczekiwanych błędów
                $this->logger->error('Nieoczekiwany błąd podczas rejestracji: ' . $e->getMessage(), ['exception' => $e]);
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas rejestracji. Spróbuj ponownie.');
                
                // Renderuj formularz ponownie z błędem
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
        }

        // Renderuj formularz rejestracji (dla pierwszego wyświetlenia lub gdy walidacja nie przeszła)
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        // Upewnij się, że użytkownik jest w pełni uwierzytelniony
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            // Obsługa potwierdzenia e-maila
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            // Obsługa błędów weryfikacji e-maila
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_register');
        }

        // Dodanie wiadomości sukcesu i przekierowanie po weryfikacji
        $this->addFlash('success', 'Twój adres e-mail został zweryfikowany.');
        return $this->redirectToRoute('app_home');
    }
}