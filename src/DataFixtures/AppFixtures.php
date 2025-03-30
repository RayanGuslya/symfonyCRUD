<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Department;
use App\Entity\User;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppFixtures extends Fixture
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
    public function load(ObjectManager $manager): void
    {   
        //departament
        $dataDeparment = [
            "LA",
            "JF",
            "SF",
            "KL"
        ];
        $dataStatus = [
            "online",
            "offline"
        ];
        foreach ($dataDeparment as $i => $name) {
            $department = new Department();
            $department->setName($name);
            $manager->persist($department);
            $this->addReference('department'.$i, $department);
            $departments[] = $department;
        }
    
        //user
        for ($i = 0; $i < 20; $i++) {
            $user = new User();

            $user->setFirstName('firstName' . $i);
            $user->setLastName('lastName' . $i);
            $user->setAge(mt_rand(10, 100));
            $user->setStatus($dataStatus[mt_rand(0,1)]);
            $user->setEmail('email' . $i . '@gmail.com');
            $user->setTelegram('telegram' . $i);
            $user->setAddress('address' . $i);
            $randomDepartmentIndex = array_rand($departments);
            $user->setDepartment($departments[$randomDepartmentIndex]);

            //проверка валидации
            $errors = $this->validator->validate($user);
 
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                throw new \RuntimeException($errorsString);
            }

            $manager->persist($user);
        }

        $manager->flush();
    }
}
