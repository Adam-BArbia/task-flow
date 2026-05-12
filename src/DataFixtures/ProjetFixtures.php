<?php

namespace App\DataFixtures;

use App\Entity\Projet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjetFixtures extends Fixture implements DependentFixtureInterface
{
    public const PROJET_REF_PREFIX = 'projet_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $statuses = ['planifie', 'en_cours', 'termine', 'annule'];

        for ($i = 0; $i < 8; ++$i) {
            $projet = new Projet();
            $dateCreation = $faker->dateTimeBetween('-120 days', '-5 days');
            $dateLimite = $faker->dateTimeBetween('-20 days', '+90 days');

            $projet->setNom(sprintf('Projet %02d - %s', $i + 1, ucfirst($faker->words(2, true))));
            $projet->setDescription($faker->optional(0.9)->paragraphs(2, true));
            $projet->setDateCreation($dateCreation);
            $projet->setDateLimite(\DateTime::createFromInterface($dateLimite));
            $projet->setStatut($statuses[$i % count($statuses)]);
            $projet->setCreateur($this->getReference(UserFixtures::USER_REF_PREFIX.$faker->numberBetween(0, 6), \App\Entity\User::class));

            $manager->persist($projet);
            $this->addReference(self::PROJET_REF_PREFIX.$i, $projet);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
