<?php

namespace App\DataFixtures;

use App\Entity\Etiquette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EtiquetteFixtures extends Fixture
{
    public const ETIQUETTE_REF_PREFIX = 'etiquette_';

    public function load(ObjectManager $manager): void
    {
        $labels = [
            ['nom' => 'Bug', 'couleur' => '#DC3545'],
            ['nom' => 'Feature', 'couleur' => '#0D6EFD'],
            ['nom' => 'Urgent', 'couleur' => '#FD7E14'],
            ['nom' => 'Documentation', 'couleur' => '#20C997'],
            ['nom' => 'Amélioration', 'couleur' => '#6F42C1'],
            ['nom' => 'Design', 'couleur' => '#E83E8C'],
        ];

        foreach ($labels as $index => $labelData) {
            $etiquette = new Etiquette();
            $etiquette->setNom($labelData['nom']);
            $etiquette->setCouleur($labelData['couleur']);

            $manager->persist($etiquette);
            $this->addReference(self::ETIQUETTE_REF_PREFIX.$index, $etiquette);
        }

        $manager->flush();
    }
}
