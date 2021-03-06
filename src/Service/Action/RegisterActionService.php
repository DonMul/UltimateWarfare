<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\User;
use FrankProjects\UltimateWarfare\Repository\MapDesignRepository;
use FrankProjects\UltimateWarfare\Repository\UserRepository;
use FrankProjects\UltimateWarfare\Service\MailService;
use FrankProjects\UltimateWarfare\Util\TokenGenerator;
use RuntimeException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class RegisterActionService
{
    /**
     * @var MapDesignRepository
     */
    private $mapDesignRepository;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * RegisterActionService constructor
     *
     * @param MapDesignRepository $mapDesignRepository
     * @param MailService $mailService
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UserRepository $userRepository
     */
    public function __construct(
        MapDesignRepository $mapDesignRepository,
        MailService $mailService,
        UserPasswordEncoderInterface $passwordEncoder,
        UserRepository $userRepository
    ) {
        $this->mapDesignRepository = $mapDesignRepository;
        $this->mailService = $mailService;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
    }

    /**
     * @param string $token
     */
    public function activateUser(string $token): void
    {
        $user = $this->userRepository->findByConfirmationToken($token);

        if (!$user) {
            throw new RunTimeException("User with this token does not exist");
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $this->userRepository->save($user);
    }

    /**
     * XXX TODO: Add captcha
     *
     * @param User $user
     */
    public function register(User $user): void
    {
        $password = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($password);

        // Get default MapDesign
        // XXX TODO: make setting?
        $mapDesign = $this->mapDesignRepository->find(3);

        try {
            $generator = new TokenGenerator();
            $token = $generator->generateToken(40);
        } catch (\Exception $exception) {
            throw new RunTimeException('TokenGenerator failed!');
        }

        $user->setMapDesign($mapDesign);
        $user->setSignup(new \DateTime());
        $user->setConfirmationToken($token);

        if ($this->userRepository->findByEmail($user->getEmail()) !== null) {
            throw new RunTimeException('User with this email already exist!');
        }

        if ($this->userRepository->findByUsername($user->getUsername()) !== null) {
            throw new RunTimeException('User with this username already exist!');
        }

        $this->userRepository->save($user);

        $this->mailService->sendRegistrationMail($user);
    }
}
