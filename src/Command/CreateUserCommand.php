<?php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Créer un utilisateur')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Login')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe provisoire (sinon demandé)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = (string) $input->getArgument('username');
        $password = (string) ($input->getArgument('password') ?? '');

        if ($password === '') {
            $helper = $this->getHelper('question');
            $q = (new Question('Mot de passe provisoire (affiché en clair):'));
            $password = $helper->ask($input, $output, $q);
        }

        $rolesMap = [
            'admin'      => 'ROLE_ADMIN',
            'member'     => 'ROLE_MEMBER',
            'guest'      => 'ROLE_GUEST',
            'restricted' => 'ROLE_RESTRICTED',
        ];

        $roleKey = $io->choice('Niveau de droit', array_keys($rolesMap), 'guest');
        $role = $rolesMap[$roleKey];

        $user = new User();
        $user->setUsername($username);
        $user->setRoles([$role]);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Utilisateur "%s" créé avec rôle %s', $username, $role));
        return Command::SUCCESS;
    }
}
