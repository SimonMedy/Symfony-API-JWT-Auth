<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserController extends AbstractController
{
    private $passwordHasher;
    private $jwtManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $jwtManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
    }

    public function login(EntityManagerInterface $em, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                return new JsonResponse(['message' => 'Veuillez fournir un email et un mot de passe.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

            if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
                return new JsonResponse(['message' => 'Identifiants invalides'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            $token = $this->jwtManager->create($user);

            return new JsonResponse(['token' => $token], JsonResponse::HTTP_OK);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (UnauthorizedHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Une erreur est survenue lors de la connexion.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function register(EntityManagerInterface $em, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                throw new BadRequestHttpException('Veuillez fournir un email et un mot de passe.');
            }

            $user = new User();
            $user->setEmail($data['email']);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $user->setRoles(['ROLE_USER']);
            $em->persist($user);
            $em->flush();

            $token = $this->jwtManager->create($user);

            return new JsonResponse(['token' => $token], Response::HTTP_CREATED);
        } catch (UniqueConstraintViolationException $e) {
            return new Response('Cet e-mail est déjà enregistré.', Response::HTTP_CONFLICT);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Une erreur est survenue lors de l\'enregistrement de l\'utilisateur.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUsers(EntityManagerInterface $em): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['message' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
            }

            $users = $em->getRepository(User::class)->findAll();

            $usersData = [];
            foreach ($users as $user) {
                $usersData[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ];
            }
            return new JsonResponse($usersData, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Une erreur est survenue lors de la récupération des utilisateurs.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserById(User $user = null): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['message' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
            }

            if (!$user) {
                throw new NotFoundHttpException('Utilisateur non trouvé.');
            }

            $userData = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ];

            return new JsonResponse($userData, JsonResponse::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Une erreur est survenue lors de la récupération de l\'utilisateur.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserByEmail(EntityManagerInterface $em, string $email): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['message' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                throw new NotFoundHttpException('Utilisateur non trouvé.');
            }

            $userData = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ];

            return new JsonResponse($userData, JsonResponse::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Une erreur est survenue lors de la récupération de l\'utilisateur.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteUser(EntityManagerInterface $em, User $user = null): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['message' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
            }

            if (!$user) {
                throw new NotFoundHttpException('Utilisateur non trouvé.');
            }

            $em->remove($user);
            $em->flush();

            return new JsonResponse(['message' => 'Utilisateur supprimé avec succès.'], JsonResponse::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteUserByEmail(EntityManagerInterface $em, string $email): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['message' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                throw new NotFoundHttpException('Utilisateur non trouvé.');
            }

            $em->remove($user);
            $em->flush();

            return new JsonResponse(['message' => 'Utilisateur supprimé avec succès.'], JsonResponse::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
