<?php

namespace App\Repository;

use App\Entity\ChatbotConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatbotConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatbotConfig::class);
    }

    public function getConfig(): ChatbotConfig
    {
        $config = $this->findOneBy([]);

        if (!$config) {
            $config = new ChatbotConfig();
            $this->getEntityManager()->persist($config);
            $this->getEntityManager()->flush();
        }

        return $config;
    }
}