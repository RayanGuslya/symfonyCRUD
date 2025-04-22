<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\User;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    hidden: false,
    aliases: ['app:add-user']
)]
class CreateUserCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private DepartmentRepository $departmentRepository;

    public function __construct(EntityManagerInterface $entityManager, DepartmentRepository $departmentRepository)
    {
        $this->entityManager = $entityManager;
        $this->departmentRepository = $departmentRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('last_name', InputArgument::REQUIRED, 'Last name of the user')
            ->addArgument('first_name', InputArgument::REQUIRED, 'First name of the user')
            ->addArgument('age', InputArgument::REQUIRED, 'Age of the user')
            ->addArgument('status', InputArgument::REQUIRED, 'Status of the user')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address')
            ->addArgument('department_id', InputArgument::REQUIRED, 'Department ID')
            ->addArgument('telegram', InputArgument::OPTIONAL, 'Telegram handle')
            ->addArgument('address', InputArgument::OPTIONAL, 'Physical address')
            ->addArgument('file_path', InputArgument::OPTIONAL, 'Path to related file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lastName = $input->getArgument('last_name');
        $firstName = $input->getArgument('first_name');
        $age = (int) $input->getArgument('age');
        $status = $input->getArgument('status');
        $email = $input->getArgument('email');
        $departmentId = (int) $input->getArgument('department_id');
        $telegram = $input->getArgument('telegram');
        $address = $input->getArgument('address');
        $filePath = $input->getArgument('file_path');

        $department = $this->departmentRepository->find($departmentId);
        if (!$department) {
            $io->error("departament $departmentId not found");
            return Command::FAILURE;
        }

        $user = new User();
        $user->setLastName($lastName);
        $user->setFirstName($firstName);
        $user->setAge($age);
        $user->setStatus($status);
        $user->setEmail($email);
        $user->setDepartment($department);
        $user->setTelegram($telegram);
        $user->setAddress($address);
        $user->setFilePath($filePath);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $io->success("user $firstName $lastName added to database");
        } catch (\Exception $e) {
            $io->error("errorr");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}