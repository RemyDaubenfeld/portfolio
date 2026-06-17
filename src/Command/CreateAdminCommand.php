<?php

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée ou met à jour le compte admin EasyAdmin',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email    = $io->ask('Email admin', 'admin@localhost');
        $password = $io->askHidden('Mot de passe');

        $repo  = $this->em->getRepository(AdminUser::class);
        $admin = $repo->findOneBy(['email' => $email]) ?? new AdminUser();

        $admin->setEmail($email);
        $admin->setPassword($this->hasher->hashPassword($admin, $password));

        $this->em->persist($admin);
        $this->em->flush();

        $io->success("Compte admin créé / mis à jour : $email");

        return Command::SUCCESS;
    }
}