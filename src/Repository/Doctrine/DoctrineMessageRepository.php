<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Repository\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FrankProjects\UltimateWarfare\Entity\Message;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Repository\MessageRepository;

final class DoctrineMessageRepository implements MessageRepository
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
     * DoctrineMessageRepository constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(Message::class);
    }

    /**
     * @param int $id
     * @return Message|null
     */
    public function find(int $id): ?Message
    {
        return $this->repository->find($id);
    }

    /**
     * @param Player $player
     * @param int $limit
     * @return Message[]
     */
    public function findNonDeletedMessagesToPlayer(Player $player, int $limit = 100): array
    {
        return $this->entityManager->createQuery(
                'SELECT m
              FROM Game:Message m
              WHERE m.toPlayer = :player AND m.toDelete = false
              ORDER BY m.timestamp DESC'
            )->setParameter('player', $player
            )->setMaxResults($limit)
            ->getResult();
    }

    /**
     * @param Player $player
     * @param int $limit
     * @return Message[]
     */
    public function findNonDeletedMessagesFromPlayer(Player $player, int $limit = 100): array
    {
        return $this->entityManager->createQuery(
                'SELECT m
              FROM Game:Message m
              WHERE m.fromPlayer = :player AND m.fromDelete = false
              ORDER BY m.timestamp DESC'
            )->setParameter('player', $player
            )->setMaxResults($limit)
            ->getResult();
    }

    /**
     * @param Message $message
     */
    public function remove(Message $message): void
    {
        $this->entityManager->remove($message);
        $this->entityManager->flush();
    }

    /**
     * @param Message $message
     */
    public function save(Message $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }
}
