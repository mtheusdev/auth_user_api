<?php

namespace App\Tests\UseCase\Auth;

use App\DTO\Auth\LoginDTO;
use App\Entity\User;
use App\Repository\User\UserRepositoryTestImpl;
use App\UseCase\Auth\LoginUserUseCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;

use function PHPSTORM_META\type;

class LoginUserUseCaseTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserPasswordHasherInterface
     */
    private $passwordHasher;
    /**
     * @var LoginUserUseCase
     */
    private $loginUser;
    /**
     * @var UserRepositoryTestImpl
     */
    private $userRepository;
    private $jwtManager;
    protected function setUp(): void
    {
        $this->userRepository = new UserRepositoryTestImpl();
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->loginUser = new LoginUserUseCase(
            $this->userRepository,
            $this->passwordHasher,
            $this->jwtManager
        );
    }

    public function testLoginUserWhenEmailNotFound(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('test@example.com');
        $existingUser->setPassword('password123');
        $this->userRepository->save($existingUser);

        $dto = new LoginDTO('otheremail@example.com', 'password123');
        $response = $this->loginUser->execute($dto);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('User not found', $response->getContent());
    }

    public function testLoginUserWhenInvalidPassword(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        $this->userRepository->save($user);


        $this->passwordHasher
            ->method('isPasswordValid')
            ->willReturn(false);

        $dto = new LoginDTO('test@example.com', 'wrongpassword');
        $response = $this->loginUser->execute($dto);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertEquals('Invalid email or password', $response->getContent());
    }

    public function testLoginUserSuccessfully(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setName('Example');
        $user->setPassword('password123');
        $this->userRepository->save($user);

        $this->passwordHasher
            ->method('isPasswordValid')
            ->willReturn(true);

        $dto = new LoginDTO('test@example.com', 'password123');
        $response = $this->loginUser->execute($dto);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Login successful', $responseData['message']);
        $this->assertEquals('test@example.com', $responseData['user']['email']);
        $this->assertEquals('Example', $responseData['user']['name']);
    }
}
