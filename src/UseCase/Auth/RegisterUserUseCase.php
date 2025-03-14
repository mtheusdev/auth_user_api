<?php

namespace App\UseCase\Auth;

use App\DTO\Auth\RegisterDTO;
use App\Entity\User;
use App\Repository\User\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;

class RegisterUserUseCase
{
    private $passwordHasher;
    private $userRepository;
    private $jwtManager;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->jwtManager = $jwtManager;
    }

    public function execute(RegisterDTO $dto): Response
    {

        $existingUser = $this->userRepository->findOneByEmail($dto->email);

        if ($existingUser) {
            return new Response('User email already exists', Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->userRepository->save($user);

        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'User created successfully!',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]
        ], Response::HTTP_CREATED);
    }
}
