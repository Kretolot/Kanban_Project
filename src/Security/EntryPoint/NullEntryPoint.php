<?php

namespace App\Security\EntryPoint;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class NullEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Tutaj możesz zwrócić Response z kodem 401 lub 403
        // W przypadku API nie chcemy przekierowywać do strony logowania.
        // Jeśli chcesz, aby zamiast tego był JSON z błędem:
        return new Response('Access Denied.', Response::HTTP_UNAUTHORIZED);
        // Lub pusty response z błędem:
        // return new Response('', Response::HTTP_UNAUTHORIZED);
        // Możesz też zwrócić JsonResponse:
        // return new JsonResponse(['error' => 'Authentication Required'], Response::HTTP_UNAUTHORIZED);
    }
}