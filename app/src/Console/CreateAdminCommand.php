<?php

declare(strict_types=1);

namespace App\Console;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create-admin',
    description: 'Creates or updates an admin user.',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = trim($io->ask('Email'));

        $helper = $this->getHelper('question');

        $passwordQuestion = new Question('Password: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(static function (?string $password): string {
            $password = (string) $password;

            if (mb_strlen($password) < 3) {
                throw new InvalidArgumentException('Password must contain at least 8 characters.');
            }

            return $password;
        });

        $password = $helper->ask($input, $output, $passwordQuestion);

        $user = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        if ($user instanceof User) {
            $io->note(sprintf('User "%s" already exists.', $email));

            $confirmUpdate = new ConfirmationQuestion(
                'Update password and add ROLE_ADMIN? [y/N] ',
                false
            );

            if (!$helper->ask($input, $output, $confirmUpdate)) {
                $io->warning('Aborted.');

                return Command::SUCCESS;
            }
        } else {
            $user = new User();
            $user->setEmail($email);

            $this->entityManager->persist($user);
        }

        $roles = $user->getRoles();
        $roles[] = 'ROLE_ADMIN';

        $roles = array_values(array_unique(array_filter(
            $roles,
            static fn (string $role): bool => $role !== 'ROLE_USER'
        )));

        $user->setRoles($roles);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        $io->success(sprintf('Admin user "%s" is ready.', $email));

        return Command::SUCCESS;
    }
}
