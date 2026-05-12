<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER_REF_PREFIX = 'user_';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $usersData = [
            [
                'email' => 'admin@taskflow.local',
                'pseudo' => 'Admin',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'Admin123!',
            ],
            [
                'email' => 'chef@taskflow.local',
                'pseudo' => 'ChefProjet',
                'roles' => ['ROLE_CHEF_PROJET'],
                'password' => 'Chef123!',
            ],
        ];

        for ($i = 0; $i < 5; ++$i) {
            $usersData[] = [
                'email' => sprintf('user%d@taskflow.local', $i + 1),
                'pseudo' => $faker->userName(),
                'roles' => ['ROLE_USER'],
                'password' => 'User123!',
            ];
        }

        foreach ($usersData as $index => $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPseudo($data['pseudo']);
            $user->setRoles($data['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

            $manager->persist($user);
            $this->addReference(self::USER_REF_PREFIX.$index, $user);
        }

        $manager->flush();
    }
}
