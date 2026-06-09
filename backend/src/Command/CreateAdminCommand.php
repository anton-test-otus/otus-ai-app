<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Создать администратора из переменных окружения',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $_ENV['ADMIN_EMAIL'] ?? null;
        $password = $_ENV['ADMIN_PASSWORD'] ?? null;

        if (!$email || !$password) {
            $io->error('ADMIN_EMAIL и ADMIN_PASSWORD должны быть установлены в .env');
            return Command::FAILURE;
        }

        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->warning(sprintf('Пользователь с email "%s" уже существует', $email));
            
            if (!in_array('ROLE_ADMIN', $existingUser->getRoles())) {
                $existingUser->setRoles(['ROLE_ADMIN']);
                $this->entityManager->flush();
                $io->success('Роль ROLE_ADMIN добавлена существующему пользователю');
            } else {
                $io->info('Пользователь уже имеет роль ROLE_ADMIN');
            }
            
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Администратор создан: %s', $email));

        return Command::SUCCESS;
    }
}
