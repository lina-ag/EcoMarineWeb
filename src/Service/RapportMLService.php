<?php

namespace App\Service;

use App\Repository\UtilisateurRepository;
use Phpml\Classification\KNearestNeighbors;
use Phpml\Clustering\KMeans;
use Phpml\Regression\LeastSquares;

class RapportMLService
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepo
    ) {}

    // Generer le rapport complet
    public function genererRapport(): array
    {
        $donnees = $this->collecterDonnees();
        $prediction = $this->predireUtilisateurs($donnees);
        $tendances = $this->analyserTendances($donnees);
        $anomalies = $this->detecterAnomalies($donnees);
        $scoresSante = $this->calculerScoreSante($donnees);

        return [
            'donnees' => $donnees,
            'prediction' => $prediction,
            'tendances' => $tendances,
            'anomalies' => $anomalies,
            'score_sante' => $scoresSante,
            'genere_le' => new \DateTime(),
        ];
    }

    // Collecter les donnees reelles
    private function collecterDonnees(): array
    {
        $tous = $this->utilisateurRepo->findAll();

        $parMois = [];
        $parRole = [];
        $parJour = [];
        $bloques = 0;

        foreach ($tous as $user) {
            if ($user->isBlocked()) {
                $bloques++;
            }

            $role = $user->getRoleName() ?? 'utilisateur';
            $parRole[$role] = ($parRole[$role] ?? 0) + 1;

            $createdAt = $user->getCreatedAt();
            if ($createdAt) {
                $mois = $createdAt->format('Y-m');
                $parMois[$mois] = ($parMois[$mois] ?? 0) + 1;

                $jour = $createdAt->format('Y-m-d');
                $parJour[$jour] = ($parJour[$jour] ?? 0) + 1;
            }
        }

        ksort($parMois);
        ksort($parJour);

        $debutSemaine = (new \DateTime())->modify('-7 days');
        $debutMois = (new \DateTime())->modify('-30 days');
        $debutMoisPrec = (new \DateTime())->modify('-60 days');

        $newSemaine = 0;
        $newMois = 0;
        $newMoisPrec = 0;

        foreach ($tous as $user) {
            $createdAt = $user->getCreatedAt();
            if (!$createdAt) {
                continue;
            }

            if ($createdAt >= $debutSemaine) {
                $newSemaine++;
            }
            if ($createdAt >= $debutMois) {
                $newMois++;
            }
            if ($createdAt >= $debutMoisPrec && $createdAt < $debutMois) {
                $newMoisPrec++;
            }
        }

        $croissance = $newMoisPrec > 0
            ? round((($newMois - $newMoisPrec) / $newMoisPrec) * 100, 1)
            : 0;

        return [
            'total' => count($tous),
            'par_mois' => $parMois,
            'par_jour' => $parJour,
            'par_role' => $parRole,
            'bloques' => $bloques,
            'actifs' => count($tous) - $bloques,
            'nouveaux_semaine' => $newSemaine,
            'nouveaux_mois' => $newMois,
            'nouveaux_mois_prec' => $newMoisPrec,
            'croissance' => $croissance,
        ];
    }

    // Prediction avec regression lineaire (ML)
    public function predireUtilisateurs(array $donnees): array
    {
        $parMois = $donnees['par_mois'];

        if (count($parMois) < 2) {
            $predictionMois = $donnees['nouveaux_mois'];
            $prediction = $donnees['total'] + $predictionMois;

            return [
                'valeur' => $prediction,
                'nouveaux_predit' => $predictionMois,
                'methode' => 'Estimation simple (donnees insuffisantes)',
                'confiance' => 50,
                'historique' => $parMois,
                'equation' => 'Projection basee sur le dernier mois connu',
            ];
        }

        $samples = [];
        $targets = [];
        $i = 1;

        foreach ($parMois as $mois => $count) {
            $samples[] = [$i];
            $targets[] = $count;
            $i++;
        }

        $regression = new LeastSquares();
        $regression->train($samples, $targets);

        $prochainMois = count($samples) + 1;
        $predictionMois = max(0, (int) $regression->predict([$prochainMois]));
        $predictionTotale = $donnees['total'] + $predictionMois;
        $confiance = $this->calculerConfiance($targets, $samples, $regression);

        return [
            'valeur' => $predictionTotale,
            'nouveaux_predit' => $predictionMois,
            'methode' => 'Regression Lineaire (PHP-ML)',
            'confiance' => $confiance,
            'historique' => $parMois,
            'equation' => 'f(x) = ax + b (Moindres Carres)',
        ];
    }

    // Analyser les tendances (KNN)
    public function analyserTendances(array $donnees): array
    {
        $parMois = $donnees['par_mois'];
        $valeurs = array_values($parMois);

        if (count($valeurs) < 3) {
            return [
                'tendance' => 'stable',
                'description' => 'Donnees insuffisantes pour analyser la tendance',
                'couleur' => '#6c757d',
                'moyenne_debut' => 0,
                'moyenne_fin' => 0,
                'difference' => 0,
                'meilleur_mois' => '-',
                'moyenne_mobile' => [],
                'valeurs' => $valeurs,
                'labels' => array_keys($parMois),
                'details' => [],
            ];
        }

        $moyenneMobile = [];
        for ($i = 1; $i < count($valeurs); $i++) {
            $moyenneMobile[] = ($valeurs[$i] + $valeurs[$i - 1]) / 2;
        }

        $debut = array_slice($valeurs, 0, (int) (count($valeurs) / 2));
        $fin = array_slice($valeurs, (int) (count($valeurs) / 2));

        $moyDebut = count($debut) > 0 ? array_sum($debut) / count($debut) : 0;
        $moyFin = count($fin) > 0 ? array_sum($fin) / count($fin) : 0;
        $diff = $moyFin - $moyDebut;

        if ($diff > 2) {
            $tendance = 'croissante';
            $description = "Les inscriptions augmentent de {$diff} utilisateurs/mois en moyenne";
            $couleur = '#28a745';
        } elseif ($diff < -2) {
            $tendance = 'decroissante';
            $description = 'Les inscriptions diminuent de ' . abs($diff) . ' utilisateurs/mois';
            $couleur = '#dc3545';
        } else {
            $tendance = 'stable';
            $description = "Les inscriptions sont stables avec une legere variation de {$diff}";
            $couleur = '#ffc107';
        }

        $meilleurMois = array_search(max($valeurs), $valeurs, true);
        $moisLabels = array_keys($parMois);

        return [
            'tendance' => $tendance,
            'description' => $description,
            'couleur' => $couleur,
            'moyenne_debut' => round($moyDebut, 1),
            'moyenne_fin' => round($moyFin, 1),
            'difference' => round($diff, 1),
            'meilleur_mois' => $moisLabels[$meilleurMois] ?? '-',
            'moyenne_mobile' => $moyenneMobile,
            'valeurs' => $valeurs,
            'labels' => $moisLabels,
        ];
    }

    // Detecter les anomalies (Z-Score)
    public function detecterAnomalies(array $donnees): array
    {
        $valeurs = array_values($donnees['par_jour']);

        if (count($valeurs) < 3) {
            return [
                'anomalies' => [],
                'moyenne' => 0,
                'ecart_type' => 0,
                'message' => 'Pas assez de donnees',
            ];
        }

        $moyenne = array_sum($valeurs) / count($valeurs);
        $variance = array_sum(array_map(fn ($v) => ($v - $moyenne) ** 2, $valeurs)) / count($valeurs);
        $ecartType = sqrt($variance);

        $anomalies = [];
        $jours = array_keys($donnees['par_jour']);

        foreach ($valeurs as $i => $val) {
            $zScore = $ecartType > 0 ? abs($val - $moyenne) / $ecartType : 0;
            if ($zScore > 2) {
                $anomalies[] = [
                    'jour' => $jours[$i],
                    'valeur' => $val,
                    'z_score' => round($zScore, 2),
                    'type' => $val > $moyenne ? 'pic' : 'creux',
                ];
            }
        }

        return [
            'anomalies' => $anomalies,
            'moyenne' => round($moyenne, 1),
            'ecart_type' => round($ecartType, 1),
            'message' => count($anomalies) > 0
                ? count($anomalies) . ' anomalie(s) detectee(s)'
                : 'Aucune anomalie detectee',
        ];
    }

    // Calculer le score de sante
    private function calculerScoreSante(array $donnees): int
    {
        $score = 100;

        $tauxBloques = $donnees['total'] > 0
            ? ($donnees['bloques'] / $donnees['total']) * 100
            : 0;

        if ($tauxBloques > 20) {
            $score -= 30;
        } elseif ($tauxBloques > 10) {
            $score -= 15;
        } elseif ($tauxBloques > 5) {
            $score -= 5;
        }

        if ($donnees['croissance'] > 10) {
            $score += 10;
        } elseif ($donnees['croissance'] > 0) {
            $score += 5;
        } elseif ($donnees['croissance'] < -10) {
            $score -= 20;
        } elseif ($donnees['croissance'] < 0) {
            $score -= 10;
        }

        if ($donnees['nouveaux_semaine'] > 5) {
            $score += 5;
        } elseif ($donnees['nouveaux_semaine'] === 0) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    // Calculer la confiance du modele
    private function calculerConfiance(array $targets, array $samples, $regression): int
    {
        if (count($targets) < 2) {
            return 50;
        }

        $moyenne = array_sum($targets) / count($targets);
        $ssTot = array_sum(array_map(fn ($y) => ($y - $moyenne) ** 2, $targets));

        if ($ssTot === 0.0) {
            return 100;
        }

        $ssRes = 0;
        foreach ($samples as $i => $sample) {
            $predicted = $regression->predict($sample);
            $ssRes += ($targets[$i] - $predicted) ** 2;
        }

        $r2 = 1 - ($ssRes / $ssTot);

        return max(0, min(100, (int) ($r2 * 100)));
    }
}
