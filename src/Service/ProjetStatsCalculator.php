<?php

namespace App\Service;

use App\Entity\Projet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to calculate statistics for projects
 */
class ProjetStatsCalculator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Get comprehensive statistics for a project
     *
     * @return array<string, int|float>
     */
    public function getProjetStats(Projet $projet): array
    {
        $taches = $projet->getTaches();
        $totalTaches = count($taches);

        if ($totalTaches === 0) {
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'pending_tasks' => 0,
                'in_progress_tasks' => 0,
                'completion_percentage' => 0,
                'average_priority_value' => 0,
                'tasks_by_assignee' => [],
                'tasks_by_status' => [
                    'a_faire' => 0,
                    'en_cours' => 0,
                    'terminee' => 0,
                ],
                'tasks_by_priority' => [
                    'basse' => 0,
                    'moyenne' => 0,
                    'haute' => 0,
                    'urgente' => 0,
                ],
            ];
        }

        $completedCount = 0;
        $pendingCount = 0;
        $inProgressCount = 0;
        $priorityValues = [];
        $byAssignee = [];
        $byStatus = ['a_faire' => 0, 'en_cours' => 0, 'terminee' => 0];
        $byPriority = ['basse' => 0, 'moyenne' => 0, 'haute' => 0, 'urgente' => 0];

        $priorityWeights = [
            'basse' => 1,
            'moyenne' => 2,
            'haute' => 3,
            'urgente' => 4,
        ];

        foreach ($taches as $tache) {
            // Count by status
            $status = $tache->getStatut();
            if ($status === 'terminee') {
                $completedCount++;
            } elseif ($status === 'en_cours') {
                $inProgressCount++;
            } elseif ($status === 'a_faire') {
                $pendingCount++;
            }
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            // Count by priority
            $priority = $tache->getPriorite();
            $priorityValues[] = $priorityWeights[$priority] ?? 2;
            $byPriority[$priority] = ($byPriority[$priority] ?? 0) + 1;

            // Count by assignee
            if ($assignee = $tache->getAssignee()) {
                $assigneeEmail = $assignee->getEmail();
                $byAssignee[$assigneeEmail] = ($byAssignee[$assigneeEmail] ?? 0) + 1;
            }
        }

        $completionPercentage = ($completedCount / $totalTaches) * 100;
        $averagePriority = !empty($priorityValues) ? array_sum($priorityValues) / count($priorityValues) : 0;

        return [
            'total_tasks' => $totalTaches,
            'completed_tasks' => $completedCount,
            'pending_tasks' => $pendingCount,
            'in_progress_tasks' => $inProgressCount,
            'completion_percentage' => round($completionPercentage, 2),
            'average_priority_value' => round($averagePriority, 2),
            'tasks_by_assignee' => $byAssignee,
            'tasks_by_status' => $byStatus,
            'tasks_by_priority' => $byPriority,
        ];
    }

    /**
     * Get statistics for all projects
     *
     * @return array<array<string, mixed>>
     */
    public function getAllProjetsStats(): array
    {
        $projets = $this->entityManager->getRepository(Projet::class)->findAll();
        $stats = [];

        foreach ($projets as $projet) {
            $stats[] = array_merge(
                ['projet_id' => $projet->getId(), 'projet_nom' => $projet->getNom()],
                $this->getProjetStats($projet)
            );
        }

        return $stats;
    }

    /**
     * Get quick overview stats for a project
     *
     * @return array<string, int|float>
     */
    public function getQuickStats(Projet $projet): array
    {
        $taches = $projet->getTaches();
        $totalTaches = count($taches);
        $completedCount = 0;

        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'terminee') {
                $completedCount++;
            }
        }

        return [
            'total' => $totalTaches,
            'completed' => $completedCount,
            'remaining' => $totalTaches - $completedCount,
            'percentage' => $totalTaches > 0 ? round(($completedCount / $totalTaches) * 100, 2) : 0,
        ];
    }
}
