<?php

namespace App\Command;

use App\Entity\User;
use App\Feature\AuthFeature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:ensure-single-user',
    description: 'Создать единственного пользователя для однопользовательского режима (idempotent)',
)]
class EnsureSingleUserCommand extends Command
{
    public function __construct(
        private AuthFeature $authFeature,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private string $singleUserEmail,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->authFeature->isEnabled()) {
            $io->warning('APP_AUTH_ENABLED=true — команда предназначена для однопользовательского режима (APP_AUTH_ENABLED=false).');
        }

        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $this->singleUserEmail]);

        if ($existingUser !== null) {
            $io->success(sprintf('Пользователь уже существует: %s', $this->singleUserEmail));

            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($this->singleUserEmail);
        $user->setRoles([]);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            bin2hex(random_bytes(32)),
        );
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Создан пользователь для single-user режима: %s', $this->singleUserEmail));

        return Command::SUCCESS;
    }
}
