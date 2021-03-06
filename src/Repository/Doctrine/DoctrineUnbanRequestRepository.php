<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Repository\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FrankProjects\UltimateWarfare\Entity\UnbanRequest;
use FrankProjects\UltimateWarfare\Entity\User;
use FrankProjects\UltimateWarfare\Repository\UnbanRequestRepository;

final class DoctrineUnbanRequestRepository implements UnbanRequestRepository
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * DoctrineUnbanRequestRepository constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(UnbanRequest::class);
    }

    /**
     * @param int $id
     * @return UnbanRequest|null
     */
    public function find(int $id): ?UnbanRequest
    {
        return $this->repository->find($id);
    }

    /**
     * @return UnbanRequest[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @param User $user
     * @return UnbanRequest|null
     */
    public function findByUser(User $user): ?UnbanRequest
    {
        return $this->repository->findOneBy(['user' => $user]);
    }

    /**
     * @param UnbanRequest $unbanRequest
     */
    public function remove(UnbanRequest $unbanRequest): void
    {
        $this->entityManager->remove($unbanRequest);
        $this->entityManager->flush();
    }

    /**
     * @param UnbanRequest $unbanRequest
     */
    public function save(UnbanRequest $unbanRequest): void
    {
        $this->entityManager->persist($unbanRequest);
        $this->entityManager->flush();
    }
}
