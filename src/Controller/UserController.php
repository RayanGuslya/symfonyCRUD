<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\DepartmentRepository; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(Request $request, UserRepository $userRepository, DepartmentRepository $departmentRepository): Response
    {
        $search = $request->query->get('search');
        $searchDepartment = $request->query->get('searchDepartment');
    
        $sort = $request->query->get('sort', 'id'); 
        $direction = $request->query->get('direction', 'ASC'); 
    
        $qb = $userRepository->createQueryBuilder('u');
        $departments = $departmentRepository->findAll();
    
        if ($search) {
            $qb->andWhere('u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($searchDepartment) {
            $qb->andWhere('u.department = :department')
               ->setParameter('department', $searchDepartment);
        }
    
        $qb->orderBy('u.' . $sort, $direction);
    
        $users = $qb->getQuery()->getResult();
    
        return $this->render('user/index.html.twig', [
            'users' => $users,
            'departments' => $departments,
            'search' => $search,
            'searchDepartment' => $searchDepartment,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/user/{user}', name: 'delete_user', methods: ["DELETE"])]
    public function delete(User $user, EntityManagerInterface $em, Filesystem $filesystem): Response
    {
        $oldFilePath = $this->getParameter('kernel.project_dir').'/public'.$user->getFilePath();

        if ($filesystem->exists($oldFilePath)) {
            $filesystem->remove($oldFilePath);
        }

        $em->remove($user);
        $em->flush();

        return $this->redirect('/user');
    }

    #[Route('/user/{user}/edit', name: 'edit_user', methods: ["GET", "POST"])]
    public function edit(User $user, EntityManagerInterface $em, Request $request, DepartmentRepository $departmentRepository,SluggerInterface $slugger,Filesystem $filesystem): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('file');

            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $user->setAge($request->request->get('age'));
            $user->setStatus($request->request->get('status'));
            $user->setEmail($request->request->get('email'));
            $user->setTelegram($request->request->get('telegram'));
            $user->setAddress($request->request->get('address'));

            if ($file && $user->getFilePath()) {
                $oldFilePath = $this->getParameter('kernel.project_dir').'/public'.$user->getFilePath();

                if ($filesystem->exists($oldFilePath)) {
                    $filesystem->remove($oldFilePath);
                }

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                $projectDir = $this->getParameter('kernel.project_dir');
                try {
                    $file->move($projectDir . "/public/uploads/files", $newFilename);
                } catch (FileException $e) {
                }
                $user->setFilePath("/uploads/files/" . $newFilename);
            }

            $departmentId = $request->request->get('department');
            $department = $departmentRepository->find($departmentId);
            $user->setDepartment($department);
            $em->flush();
            return $this->redirectToRoute('app_user');
        }
        $departments = $departmentRepository->findAll();
        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'departments' => $departments,
        ]);
    }
        #[Route('/user/create', name: 'create_user', methods: ['POST'])]
        public function create(Request $request, EntityManagerInterface $entityManager, DepartmentRepository $departmentRepository, SluggerInterface $slugger): Response
        {
            $user = new User();
            $file = $request->files->get('file');
            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $user->setAge($request->request->get('age'));
            $user->setStatus($request->request->get('status'));
            $user->setEmail($request->request->get('email'));
            $user->setTelegram($request->request->get('telegram'));
            $user->setAddress($request->request->get('address'));
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                $projectDir = $this->getParameter('kernel.project_dir');
                try {
                    $file->move($projectDir . "/public/uploads/files", $newFilename);
                } catch (FileException $e) {

                }   
                $user->setFilePath("/uploads/files/" . $newFilename);
            }

            $departmentId = $request->request->get('department');
            $department = $departmentRepository->find($departmentId);
            $user->setDepartment($department);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user');
        }

    #[Route('/user/create')]
    public function formCreate(DepartmentRepository $departmentRepository): Response
    {
        $departments = $departmentRepository->findAll();
        return $this->render('user/create.html.twig', [
            'departments' => $departments,
        ]);
    }
}