<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use TypeError;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedHttpException) {
            $response = new JsonResponse(['message' => 'Accès Refusé'], JsonResponse::HTTP_FORBIDDEN);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $response = new JsonResponse(['message' => 'Ressource non trouvée'], JsonResponse::HTTP_NOT_FOUND);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            $response = new JsonResponse(['message' => 'Méthode non authorisé'], JsonResponse::HTTP_METHOD_NOT_ALLOWED);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof BadRequestHttpException) {
            $response = new JsonResponse(['message' => 'Requête incorrecte.'], JsonResponse::HTTP_BAD_REQUEST);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof TypeError) {
            $response = new JsonResponse(['message' => 'Erreur de type : ' . $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse(['message' => $exception->getMessage()], $exception->getStatusCode());
            $event->setResponse($response);
            return;
        } else {
            $response = new JsonResponse(['message' => 'Une erreur est survenue, vérifiez votre requête'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            $event->setResponse($response);
            return;
        }

    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
