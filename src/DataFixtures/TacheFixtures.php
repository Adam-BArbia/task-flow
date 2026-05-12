<?php

namespace App\DataFixtures;

use App\Entity\Tache;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class TacheFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $priorities = ['basse', 'moyenne', 'haute', 'urgente'];
        $statuses = ['a_faire', 'en_cours', 'terminee'];

        for ($i = 0; $i < 40; ++$i) {
            $tache = new Tache();

            $tache->setTitre(ucfirst($faker->words($faker->numberBetween(2, 5), true)));
            $tache->setDescription($faker->optional(0.85)->sentence(18));
            $tache->setPriorite($priorities[$faker->numberBetween(0, count($priorities) - 1)]);
            $tache->setStatut($statuses[$faker->numberBetween(0, count($statuses) - 1)]);

            if ($faker->boolean(70)) {
                $dueDate = $faker->dateTimeBetween('-20 days', '+45 days');
                $tache->setDateEcheance(\DateTime::createFromInterface($dueDate));
            }

            $tache->setProjet($this->getReference(ProjetFixtures::PROJET_REF_PREFIX.$faker->numberBetween(0, 7), \App\Entity\Projet::class));

            if ($faker->boolean(75)) {
                $tache->setAssignee($this->getReference(UserFixtures::USER_REF_PREFIX.$faker->numberBetween(0, 6), \App\Entity\User::class));
            }

            $labelIndexes = $faker->randomElements(range(0, 5), $faker->numberBetween(1, 3));
            foreach ($labelIndexes as $labelIndex) {
                $tache->addEtiquette($this->getReference(EtiquetteFixtures::ETIQUETTE_REF_PREFIX.$labelIndex, \App\Entity\Etiquette::class));
            }

            $manager->persist($tache);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjetFixtures::class,
            UserFixtures::class,
            EtiquetteFixtures::class,
        ];
    }
}
