<?php

namespace App\Entity;

use App\Repository\TacheRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['tache:read']],
    denormalizationContext: ['groups' => ['tache:write']],
)]
#[ORM\Entity(repositoryClass: TacheRepository::class)]
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tache:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 10)]
    #[Assert\Choice(['basse', 'moyenne', 'haute', 'urgente'])]
    #[Groups(['tache:read', 'tache:write'])]
    private ?string $priorite = 'moyenne';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['a_faire', 'en_cours', 'terminee'])]
    #[Groups(['tache:read', 'tache:write'])]
    private ?string $statut = 'a_faire';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['tache:read'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?\DateTimeInterface $dateEcheance = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?string $pieceJointeName = null;

    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'taches')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?Projet $projet = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'taches')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?User $assignee = null;

    /**
     * @var Collection<int, Etiquette>
     */
    #[ORM\ManyToMany(targetEntity: Etiquette::class)]
    #[ORM\JoinTable(name: 'tache_etiquette')]
    #[Groups(['tache:read', 'tache:write'])]
    private Collection $etiquettes;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->etiquettes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateEcheance(): ?\DateTimeInterface
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?\DateTimeInterface $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;

        return $this;
    }

    public function getPieceJointeName(): ?string
    {
        return $this->pieceJointeName;
    }

    public function setPieceJointeName(?string $pieceJointeName): static
    {
        $this->pieceJointeName = $pieceJointeName;

        return $this;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function setProjet(?Projet $projet): static
    {
        $this->projet = $projet;

        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;

        return $this;
    }

    /**
     * @return Collection<int, Etiquette>
     */
    public function getEtiquettes(): Collection
    {
        return $this->etiquettes;
    }

    public function addEtiquette(Etiquette $etiquette): static
    {
        if (!$this->etiquettes->contains($etiquette)) {
            $this->etiquettes->add($etiquette);
        }

        return $this;
    }

    public function removeEtiquette(Etiquette $etiquette): static
    {
        $this->etiquettes->removeElement($etiquette);

        return $this;
    }
}
