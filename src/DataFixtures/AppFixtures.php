<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@taskflow.local');
        $user->setPseudo('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'User123!'));

        $manager->persist($user);

        $chefProjet = new User();
        $chefProjet->setEmail('chef@taskflow.local');
        $chefProjet->setPseudo('ChefProjet');
        $chefProjet->setRoles(['ROLE_CHEF_PROJET']);
        $chefProjet->setPassword($this->passwordHasher->hashPassword($chefProjet, 'Chef123!'));

        $manager->persist($chefProjet);

        $admin = new User();
        $admin->setEmail('admin@taskflow.local');
        $admin->setPseudo('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));

        $manager->persist($admin);

        $manager->flush();
    }
}
