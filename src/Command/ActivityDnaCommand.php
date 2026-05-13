<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Phpml\Clustering\KMeans;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ml:activity-dna',
    description: 'Analyse le DNA comportemental de chaque activité écologique via KMeans clustering',
)]
class ActivityDnaCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🧬 EcoMarine — Analyse DNA des Activités');

        // ── 1. CHARGEMENT DES DONNÉES ─────────────────────────────────────────
        $rows = $this->entityManager->getConnection()->executeQuery('
            SELECT
                a.id_activite,
                a.nom_activite,
                a.capacite,
                MONTH(a.date_activite)                                               AS mois,
                DAYOFWEEK(a.date_activite)                                           AS jour_semaine,
                COUNT(r.id_reservation)                                              AS nb_reservations,
                COALESCE(SUM(r.nombre_personnes), 0)                                 AS total_personnes,
                COALESCE(AVG(r.nombre_personnes), 0)                                 AS avg_groupe,
                COALESCE(MIN(r.nombre_personnes), 0)                                 AS min_groupe,
                COALESCE(MAX(r.nombre_personnes), 0)                                 AS max_groupe,
                COALESCE(AVG(ABS(DATEDIFF(a.date_activite, r.date_reservation))), 0) AS avg_delai_jours,
                COALESCE(MIN(ABS(DATEDIFF(a.date_activite, r.date_reservation))), 0) AS min_delai_jours,
                COALESCE(MAX(ABS(DATEDIFF(a.date_activite, r.date_reservation))), 0) AS max_delai_jours,
                COUNT(DISTINCT r.email)                                              AS nb_emails_uniques
            FROM activite_ecologique a
            LEFT JOIN reservation r ON r.id_activite = a.id_activite
            GROUP BY a.id_activite, a.nom_activite, a.capacite, mois, jour_semaine
        ')->fetchAllAssociative();

        if (count($rows) < 3) {
            $io->error('Pas assez de données pour effectuer le clustering (minimum 3 activités).');
            return Command::FAILURE;
        }

        $io->info(sprintf('✅ %d activités chargées depuis la base de données.', count($rows)));

        // ── 2. CONSTRUCTION DES FEATURES ─────────────────────────────────────
        $samples     = [];
        $activiteMap = [];

        foreach ($rows as $row) {
            $capacite        = max(1, (int) $row['capacite']);
            $tauxRemplissage = min(1.0, (float) $row['total_personnes'] / $capacite);

            $samples[]     = [
                (float) $row['avg_groupe'],
                (float) $row['avg_delai_jours'],
                (float) $tauxRemplissage,
                (float) $row['nb_reservations'],
                (float) $row['mois'],
            ];
            $activiteMap[] = $row;
        }

        // ── 3. NORMALISATION MIN-MAX ──────────────────────────────────────────
        $nbFeatures = count($samples[0]);
        $mins = array_fill(0, $nbFeatures, PHP_INT_MAX);
        $maxs = array_fill(0, $nbFeatures, PHP_INT_MIN);

        foreach ($samples as $sample) {
            for ($i = 0; $i < $nbFeatures; $i++) {
                if ($sample[$i] < $mins[$i]) $mins[$i] = $sample[$i];
                if ($sample[$i] > $maxs[$i]) $maxs[$i] = $sample[$i];
            }
        }

        $normalizedSamples = [];
        foreach ($samples as $sample) {
            $normalized = [];
            for ($i = 0; $i < $nbFeatures; $i++) {
                $range        = $maxs[$i] - $mins[$i];
                $normalized[] = $range > 0 ? ($sample[$i] - $mins[$i]) / $range : 0.0;
            }
            $normalizedSamples[] = $normalized;
        }

        // ── 4. KMEANS CLUSTERING ──────────────────────────────────────────────
        $nbClusters = min(3, count($normalizedSamples));
        $kmeans     = new KMeans($nbClusters);
        $clusters   = $kmeans->cluster($normalizedSamples);

        // ── 5. MAP CLUSTER → ACTIVITÉ ─────────────────────────────────────────
        $clusterAssignments = [];
        $usedIndexes = [];
        foreach ($clusters as $clusterId => $clusterSamples) {
            foreach ($clusterSamples as $sample) {
                foreach ($normalizedSamples as $idx => $ns) {
                    if (isset($usedIndexes[$idx])) {
                        continue;
                    }

                    if ($ns === $sample) {
                        $clusterAssignments[$idx] = $clusterId;
                        $usedIndexes[$idx] = true;
                        break;
                    }
                }
            }
        }

        // Safety net: if a point was not matched (floating equality edge case),
        // assign it to the nearest cluster centroid to keep output complete.
        if (count($clusterAssignments) < count($normalizedSamples)) {
            $centroids = [];
            foreach ($clusters as $clusterId => $clusterSamples) {
                if (count($clusterSamples) === 0) {
                    continue;
                }

                $centroid = array_fill(0, $nbFeatures, 0.0);
                foreach ($clusterSamples as $clusterSample) {
                    for ($i = 0; $i < $nbFeatures; $i++) {
                        $centroid[$i] += (float) $clusterSample[$i];
                    }
                }

                for ($i = 0; $i < $nbFeatures; $i++) {
                    $centroid[$i] /= count($clusterSamples);
                }

                $centroids[$clusterId] = $centroid;
            }

            foreach ($normalizedSamples as $idx => $sample) {
                if (isset($clusterAssignments[$idx])) {
                    continue;
                }

                $closestCluster = null;
                $closestDistance = INF;

                foreach ($centroids as $clusterId => $centroid) {
                    $distance = 0.0;
                    for ($i = 0; $i < $nbFeatures; $i++) {
                        $distance += ($sample[$i] - $centroid[$i]) ** 2;
                    }

                    if ($distance < $closestDistance) {
                        $closestDistance = $distance;
                        $closestCluster = $clusterId;
                    }
                }

                if ($closestCluster !== null) {
                    $clusterAssignments[$idx] = $closestCluster;
                }
            }
        }

        // ── 6. CALCUL DES STATS PAR CLUSTER ──────────────────────────────────
        $clusterStats = [];
        foreach ($clusterAssignments as $idx => $clusterId) {
            $row      = $activiteMap[$idx];
            $capacite = max(1, (int) $row['capacite']);
            if (!isset($clusterStats[$clusterId])) {
                $clusterStats[$clusterId] = ['avg_groupe' => [], 'avg_delai' => [], 'taux' => [], 'nb_reservations' => []];
            }
            $clusterStats[$clusterId]['avg_groupe'][]      = (float) $row['avg_groupe'];
            $clusterStats[$clusterId]['avg_delai'][]       = (float) $row['avg_delai_jours'];
            $clusterStats[$clusterId]['taux'][]            = min(1.0, (float) $row['total_personnes'] / $capacite);
            $clusterStats[$clusterId]['nb_reservations'][] = (int) $row['nb_reservations'];
        }

        // ── 7. LABELS DES CLUSTERS ────────────────────────────────────────────
        $clusterLabels = [];
        foreach ($clusterStats as $clusterId => $stats) {
            $avgTaux   = array_sum($stats['taux']) / count($stats['taux']);
            $avgGroupe = array_sum($stats['avg_groupe']) / count($stats['avg_groupe']);
            $avgDelai  = abs(array_sum($stats['avg_delai']) / count($stats['avg_delai']));

            if ($avgTaux >= 0.6) {
                $label = '🔥 Très populaire';
                $color = 'danger';
            } elseif ($avgTaux >= 0.3) {
                $label = '📊 Popularité moyenne';
                $color = 'warning';
            } else {
                $label = '🧊 Peu demandée';
                $color = 'info';
            }

            $clusterLabels[$clusterId] = [
                'label'      => $label,
                'color'      => $color,
                'avg_taux'   => round($avgTaux * 100, 1),
                'avg_groupe' => round($avgGroupe, 1),
                'avg_delai'  => round($avgDelai, 1),
            ];
        }

        // ── 8. AFFICHAGE CONSOLE ──────────────────────────────────────────────
        $io->section('📊 Résultats du clustering KMeans');

        $tableData = [];
        foreach ($clusterAssignments as $idx => $clusterId) {
            $row      = $activiteMap[$idx];
            $capacite = max(1, (int) $row['capacite']);
            $taux     = min(100, round((float) $row['total_personnes'] / $capacite * 100, 1));

            $joursNoms   = ['', 'Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            $moisNoms    = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

            $tableData[] = [
                $row['id_activite'],
                mb_strimwidth((string) $row['nom_activite'], 0, 25, '…'),
                ($moisNoms[(int) $row['mois']] ?? '?') . ' ' . ($joursNoms[(int) $row['jour_semaine']] ?? '?'),
                $row['nb_reservations'],
                $row['avg_groupe'] > 0 ? round((float) $row['avg_groupe'], 1) : '—',
                $row['avg_delai_jours'] > 0 ? round((float) $row['avg_delai_jours'], 0) . 'j' : '—',
                $taux . '%',
                $clusterLabels[$clusterId]['label'],
            ];
        }

        $io->table(
            ['ID', 'Activité', 'Période', 'Réservations', 'Moy. groupe', 'Délai moy.', 'Taux', 'DNA'],
            $tableData
        );

        $io->section('🧬 Résumé des profils DNA');
        foreach ($clusterLabels as $clusterId => $info) {
            $count = count(array_filter($clusterAssignments, fn($c) => $c === $clusterId));
            $io->writeln(sprintf(
                '  <info>Cluster %d</info> — %s : <comment>%d activité(s)</comment> | Taux moyen : <comment>%s%%</comment> | Groupe moyen : <comment>%s pers.</comment> | Délai moyen : <comment>%s jours</comment>',
                $clusterId + 1,
                $info['label'],
                $count,
                $info['avg_taux'],
                $info['avg_groupe'],
                $info['avg_delai']
            ));
        }

        // ── 9. SAUVEGARDE JSON pour Symfony ──────────────────────────────────
        
        // Chargement des données détaillées pour taux de retour et saisonnalité
        $detailedRows = $this->entityManager->getConnection()->executeQuery('
            SELECT
                a.id_activite,
                a.nom_activite,
                a.capacite,
                MONTH(a.date_activite) AS mois,
                DAYOFWEEK(a.date_activite) AS jour_semaine,
                r.email,
                r.nombre_personnes,
                r.date_reservation,
                a.date_activite
            FROM activite_ecologique a
            LEFT JOIN reservation r ON r.id_activite = a.id_activite
            ORDER BY a.id_activite, r.date_reservation
        ')->fetchAllAssociative();

        // Calcul des statistiques détaillées par activité
        $activityDetails = [];
        foreach ($detailedRows as $row) {
            $idActivite = (int) $row['id_activite'];
            if (!isset($activityDetails[$idActivite])) {
                $activityDetails[$idActivite] = [
                    'nom_activite' => $row['nom_activite'],
                    'capacite' => (int) $row['capacite'],
                    'reservations' => [],
                    'reservations_by_email' => [],
                    'reservations_by_dayofweek' => [],
                    'reservations_by_month' => [],
                ];
            }
            
            if ($row['email'] && $row['nombre_personnes']) {
                $activityDetails[$idActivite]['reservations'][] = [
                    'email' => $row['email'],
                    'nombre_personnes' => (int) $row['nombre_personnes'],
                    'date_reservation' => $row['date_reservation'],
                    'date_activite' => $row['date_activite'],
                ];
                
                // Comptage des réservations par email (pour taux de retour)
                if (!isset($activityDetails[$idActivite]['reservations_by_email'][$row['email']])) {
                    $activityDetails[$idActivite]['reservations_by_email'][$row['email']] = 0;
                }
                $activityDetails[$idActivite]['reservations_by_email'][$row['email']]++;
                
                // Comptage par jour de la semaine
                $jour = (int) $row['jour_semaine'];
                if (!isset($activityDetails[$idActivite]['reservations_by_dayofweek'][$jour])) {
                    $activityDetails[$idActivite]['reservations_by_dayofweek'][$jour] = 0;
                }
                $activityDetails[$idActivite]['reservations_by_dayofweek'][$jour]++;
                
                // Comptage par mois
                $mois = (int) $row['mois'];
                if (!isset($activityDetails[$idActivite]['reservations_by_month'][$mois])) {
                    $activityDetails[$idActivite]['reservations_by_month'][$mois] = 0;
                }
                $activityDetails[$idActivite]['reservations_by_month'][$mois]++;
            }
        }

        $outputData = [];
        foreach ($clusterAssignments as $idx => $clusterId) {
            $row      = $activiteMap[$idx];
            $capacite = max(1, (int) $row['capacite']);
            $idActivite = (int) $row['id_activite'];
            $taux = min(100, round((float) $row['total_personnes'] / $capacite * 100, 1));
            
            // Calcul taux de retour (clients fidèles)
            $tauxRetour = 0;
            if (isset($activityDetails[$idActivite])) {
                $emailsWithMultiple = array_filter($activityDetails[$idActivite]['reservations_by_email'], fn($count) => $count > 1);
                $totalEmails = count($activityDetails[$idActivite]['reservations_by_email']);
                $tauxRetour = $totalEmails > 0 ? round(count($emailsWithMultiple) / $totalEmails * 100, 1) : 0;
            }
            
            // Jour de semaine de pic
            $jourPic = null;
            $nbPic = 0;
            if (isset($activityDetails[$idActivite]['reservations_by_dayofweek'])) {
                foreach ($activityDetails[$idActivite]['reservations_by_dayofweek'] as $jour => $count) {
                    if ($count > $nbPic) {
                        $nbPic = $count;
                        $jourPic = $jour;
                    }
                }
            }
            
            $joursNoms = ['', 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            $jourPicName = $jourPic ? ($joursNoms[$jourPic] ?? '?') : 'Aucun';
            
            // Sensibilité saisonnière (comparaison été vs hiver)
            $saisonEte = 0;
            $saisonHiver = 0;
            if (isset($activityDetails[$idActivite]['reservations_by_month'])) {
                // Été: juin, juillet, août (mois 6, 7, 8)
                // Hiver: novembre, décembre, janvier, février (mois 11, 12, 1, 2)
                foreach ($activityDetails[$idActivite]['reservations_by_month'] as $mois => $count) {
                    if (in_array($mois, [6, 7, 8])) {
                        $saisonEte += $count;
                    } elseif (in_array($mois, [11, 12, 1, 2])) {
                        $saisonHiver += $count;
                    }
                }
            }
            
            $ratiosaison = $saisonHiver > 0 ? round($saisonEte / $saisonHiver, 1) : 0;
            
            // Vitesse de remplissage (basée sur la distribution des délais)
            $vitesseFill = 'Moyen';
            if ((float) $row['avg_delai_jours'] < 7) {
                $vitesseFill = '⚡ Rapide';
            } elseif ((float) $row['avg_delai_jours'] > 21) {
                $vitesseFill = '🐢 Lent';
            }
            
            $outputData[(int) $row['id_activite']] = [
                'id_activite'              => (int) $row['id_activite'],
                'nom_activite'             => $row['nom_activite'],
                'cluster'                  => $clusterId,
                'dna_label'                => $clusterLabels[$clusterId]['label'],
                'dna_color'                => $clusterLabels[$clusterId]['color'],
                'taux'                     => $taux,
                'avg_groupe'               => round((float) $row['avg_groupe'], 1),
                'min_groupe'               => (int) $row['min_groupe'],
                'max_groupe'               => (int) $row['max_groupe'],
                'avg_delai'                => round((float) $row['avg_delai_jours'], 1),
                'min_delai'                => (int) $row['min_delai_jours'],
                'max_delai'                => (int) $row['max_delai_jours'],
                'nb_reservations'          => (int) $row['nb_reservations'],
                'taux_retour'              => $tauxRetour,
                'jour_pic'                 => $jourPicName,
                'sensibilite_saisonniere'  => $ratiosaison,
                'vitesse_remplissage'      => $vitesseFill,
            ];
        }

        $jsonPath = __DIR__ . '/../../var/activity_dna.json';
        file_put_contents($jsonPath, json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $io->success('DNA sauvegardé dans var/activity_dna.json — les badges apparaîtront dans l\'interface.');

        return Command::SUCCESS;
    }
}