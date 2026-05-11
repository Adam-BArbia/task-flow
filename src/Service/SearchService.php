<?php

namespace App\Service;

use App\Entity\Projet;
use App\Entity\Tache;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;

/**
 * Service to search and filter projects and tasks
 */
class SearchService
{
    public function __construct(
        private ProjetRepository $projetRepository,
        private TacheRepository $tacheRepository
    ) {}

    /**
     * Search projects by name, description, or status
     *
     * @param string $query Search query
     * @param string|null $status Filter by status (planifie|en_cours|termine|annule)
     * @return Projet[]
     */
    public function searchProjets(string $query = '', ?string $status = null): array
    {
        $query = trim($query);
        $status = $this->normalizeFilter($status);

        $projets = $this->projetRepository->findAll();
        
        return array_filter($projets, function (Projet $projet) use ($query, $status) {
            $matchesQuery = empty($query) || 
                str_contains(strtolower($projet->getNom()), strtolower($query)) ||
                str_contains(strtolower((string)$projet->getDescription()), strtolower($query));
            
            $matchesStatus = $status === null || $projet->getStatut() === $status;
            
            return $matchesQuery && $matchesStatus;
        });
    }

    /**
     * Search tasks by title, description, status, or priority
     *
     * @param string $query Search query
     * @param string|null $status Filter by status (a_faire|en_cours|terminee)
     * @param string|null $priority Filter by priority (basse|moyenne|haute|urgente)
     * @param int|null $projetId Filter by project ID
     * @return Tache[]
     */
    public function searchTaches(
        string $query = '',
        ?string $status = null,
        ?string $priority = null,
        ?int $projetId = null
    ): array {
        $query = trim($query);
        $status = $this->normalizeFilter($status);
        $priority = $this->normalizeFilter($priority);

        $taches = $this->tacheRepository->findAll();
        
        return array_filter($taches, function (Tache $tache) use ($query, $status, $priority, $projetId) {
            $matchesQuery = empty($query) || 
                str_contains(strtolower($tache->getTitre()), strtolower($query)) ||
                str_contains(strtolower((string)$tache->getDescription()), strtolower($query));
            
            $matchesStatus = $status === null || $tache->getStatut() === $status;
            
            $matchesPriority = $priority === null || $tache->getPriorite() === $priority;
            
            $matchesProjet = $projetId === null || $tache->getProjet()->getId() === $projetId;
            
            return $matchesQuery && $matchesStatus && $matchesPriority && $matchesProjet;
        });
    }

    /**
     * Advanced filtering for projects with QueryBuilder (for future optimization)
     *
     * @param string $query Search query
     * @param string|null $status Filter by status
     * @param string|null $sortBy Sort by field (nom|dateCreation|dateLimite)
     * @param string $sortOrder asc or desc
     * @return Projet[]
     */
    public function advancedProjetsSearch(
        string $query = '',
        ?string $status = null,
        string $sortBy = 'nom',
        string $sortOrder = 'asc'
    ): array {
        $projets = $this->searchProjets($query, $status);
        
        usort($projets, function (Projet $a, Projet $b) use ($sortBy, $sortOrder) {
            $aValue = match ($sortBy) {
                'dateCreation' => $a->getDateCreation(),
                'dateLimite' => $a->getDateLimite(),
                default => $a->getNom(),
            };
            
            $bValue = match ($sortBy) {
                'dateCreation' => $b->getDateCreation(),
                'dateLimite' => $b->getDateLimite(),
                default => $b->getNom(),
            };
            
            $comparison = $aValue <=> $bValue;
            return $sortOrder === 'desc' ? -$comparison : $comparison;
        });
        
        return $projets;
    }

    /**
     * Advanced filtering for tasks
     *
     * @param string $query Search query
     * @param string|null $status Filter by status
     * @param string|null $priority Filter by priority
     * @param int|null $projetId Filter by project
     * @param string $sortBy Sort by field (titre|dateCreation|dateEcheance|priorite)
     * @param string $sortOrder asc or desc
     * @return Tache[]
     */
    public function advancedTachesSearch(
        string $query = '',
        ?string $status = null,
        ?string $priority = null,
        ?int $projetId = null,
        string $sortBy = 'titre',
        string $sortOrder = 'asc'
    ): array {
        $taches = $this->searchTaches($query, $status, $priority, $projetId);
        
        $priorityOrder = ['basse' => 1, 'moyenne' => 2, 'haute' => 3, 'urgente' => 4];
        
        usort($taches, function (Tache $a, Tache $b) use ($sortBy, $sortOrder, $priorityOrder) {
            $aValue = match ($sortBy) {
                'priorite' => $priorityOrder[$a->getPriorite()] ?? 2,
                'dateCreation' => $a->getDateCreation(),
                'dateEcheance' => $a->getDateEcheance(),
                default => $a->getTitre(),
            };
            
            $bValue = match ($sortBy) {
                'priorite' => $priorityOrder[$b->getPriorite()] ?? 2,
                'dateCreation' => $b->getDateCreation(),
                'dateEcheance' => $b->getDateEcheance(),
                default => $b->getTitre(),
            };
            
            $comparison = $aValue <=> $bValue;
            return $sortOrder === 'desc' ? -$comparison : $comparison;
        });
        
        return $taches;
    }

    private function normalizeFilter(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
