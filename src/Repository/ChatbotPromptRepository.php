<?php

namespace App\Repository;

use App\Entity\ChatbotPrompt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatbotPromptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatbotPrompt::class);
    }

    public function findActive(): array
    {
        return $this->findBy(
            ['isActive' => true],
            ['position' => 'ASC']
        );
    }
}