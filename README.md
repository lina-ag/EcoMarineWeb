# EcoMarine 🌊

An ecotourism web platform built with Symfony 6, dedicated to the preservation of Kuriat Island, Monastir, Tunisia.

This project was developed as part of the coursework for **PIDEV 3A** at **Esprit School of Engineering** — Academic year 2024-2025.

---

## Table des Matières

- [Description](#description)
- [Fonctionnalités](#fonctionnalités)
- [Tech Stack](#tech-stack)
- [Structure du projet](#structure-du-projet)
- [Installation](#installation)
- [Contributions](#contributions)
- [Licence](#licence)
- [Acknowledgments](#acknowledgments)

---

## Description

EcoMarine est une plateforme web dédiée à l'écotourisme marin autour de l'île de Kuriat, Monastir, Tunisie. Elle centralise la gestion des zones marines protégées, la surveillance de la faune, les missions drone, les signalements de déchets, les actions bénévoles de nettoyage, les activités écologiques et réservations — le tout enrichi par des modules IA avancés (Groq, Ollama, PHP-ML, Python ML).

---

## Fonctionnalités

### 🏕️ Module Activités Écologiques & Réservations

#### Activités Écologiques
- CRUD complet des activités (liste, création, détail, édition, suppression)
- Recherche, filtrage, tri et pagination côté serveur (champs : q, field, periode, capacite, sort)
- Widget météo Monastir affiché sur la page activités (WeatherService + API Ninjas)
- Activités récurrentes : sélection des jours de la semaine + date de fin, génération automatique d'occurrences
- Export PDF global de toutes les activités (KnpSnappy + Dompdf)
- Page DNA d'une activité + export PDF DNA
- Génération IA de description d'activité (Python TF-IDF + similarité cosinus — DescriptionAIService)
- Prédiction IA du risque de sous-remplissage (Python DecisionTree — PredictionAIService + predict.py)
- DNA clustering KMeans des activités (PHP-ML — commande `app:ml:activity-dna`, sortie JSON)

#### Réservations
- CRUD complet des réservations (liste, création, détail, édition, suppression)
- Recherche, filtrage, tri et pagination côté serveur (champs : q, field, status, personnes, date_from, date_to, sort)
- Contrôle dynamique des capacités : disponible / presque complet / complet
- Sélection de date selon l'activité (multi-dates si activité récurrente)
- Envoi automatique d'email de confirmation après création (Gmail SMTP)
- Quiz écologique post-réservation avec badge bronze / argent / or
- Visualisation Chart.js des réservations par activité
- Exports PDF : liste complète, fiche unitaire, rapport IA en PDF
- Export CSV filtré depuis la liste
- Rapport IA des réservations (Groq API — GroqReportService)

#### Modèles de données
- `ActiviteEcologique` : nom, date, capacité, description — OneToMany réservations
- `Reservation` : nom, date, email, nombre_personnes, badge quiz — ManyToOne activité

#### Services Python IA
- `predict.py` + `train_model.py` : entraînement DecisionTree, modèle sérialisé (`model.pkl`)
- `generate_description.py` : génération de description par TF-IDF + similarité
- `api.py` : API Flask vision/classification YOLO (`POST /predict`)
- `face_recognition_api.py` : API Flask reconnaissance faciale (`GET /health`, `POST /extract_encoding`, `POST /compare_faces`, `POST /verify_face`)

---

### 🗑️ Module Déchets & Signalements
- Ajout de signalements de déchets avec géolocalisation
- KPI : nombre de signalements, quantité totale, zones suivies, progression du nettoyage
- Carte interactive des zones d'intervention (Monastir, Kuriat Nord/Sud, Sousse, Mahdia, Sfax)
- Affichage des points de signalement sur la carte
- Statistiques et consultation des documents liés aux déchets
- Assistant IA avec questions rapides sur les zones prioritaires et les KPI
- Briefing IA sur les zones nécessitant une intervention rapide
- Visualisation tactique des signalements actifs sur le littoral
- Changement de langue Français / Anglais

---

### 🌍 Module Zones Protégées & Surveillances Marines
- CRUD complet des zones marines protégées (nom, catégorie, statut)
- Suivi des surveillances par zone avec observations scientifiques
- Recherche en temps réel et autocomplétion intelligente (AJAX + JSON)
- Filtrage par statut : Actif / En surveillance / En maintenance / Inactif
- Carte interactive Leaflet avec 3 styles (Nuit, Minimal, Satellite)
- Recherche vocale intégrée (Web Speech API)
- Prédiction IA du risque de dégradation d'une zone (Groq API)
- Rapport IA automatique des surveillances (Groq + llama3-8b-8192)
- Export PDF (KnpSnappy + Dompdf) et Export Excel (PhpSpreadsheet)
- Pagination (KnpPaginatorBundle)
- Recherche Elasticsearch avancée avec fuzzy matching (FOSElasticaBundle)

---

### 🧹 Module Gestion Bénévole & Nettoyage
- Consultation et filtrage des actions de nettoyage (lieu, date, statut)
- Participation avec vérification CSRF, anti-double inscription et gestion des places
- Suivi dans "Mes actions de nettoyage" avec possibilité d'annulation
- Recommandations personnalisées IA (3 actions suggérées, fallback par date si IA indisponible)
- Remplacement automatique d'une recommandation après participation
- CRUD admin des actions avec gestion de la limite de bénévoles
- Rapport IA des actions + Export PDF
- Statistiques : participants, capacité, taux de remplissage, régions, actions passées
- Redirection vers connexion si non connecté, retour automatique après login

---

### 👥 Module Gestion des Utilisateurs & Sécurité
- CRUD complet des comptes utilisateurs (nom, email, rôle, date de naissance)
- Authentification classique + reconnaissance faciale (DeepFace)
- Mot de passe oublié avec réinitialisation par token sécurisé (Mailtrap)
- Blocage/Déblocage en temps réel (AJAX) avec indicateur visuel
- Restriction géographique par pays via détection IP (GeoIP)
- Gestion des pays bloqués avec interface admin dédiée
- Rapport IA avec PHP-ML : Régression Linéaire, Moyenne Mobile, Z-Score, Score de santé (0-100)
- Protection anti-spam (omines/antispam-bundle)
- Export Excel (PhpSpreadsheet) + Pagination (KnpPaginatorBundle)
- API REST sécurisée pour utilisateurs et pays bloqués

---

### 🐋 Module Faune Marine & Observations
- CRUD des espèces marines (nom, état de santé, description)
- CRUD des observations liées (date, température, météo, animal associé)
- Recherche et filtrage en temps réel (date, espèce, état)
- Statistiques : total observations, température moyenne, conditions météo dominantes, min/max température

---

### 🛸 Module Mission Drone
- CRUD des missions drone (date, heure, zone, altitude, distance, conditions de vol)
- Historique chronologique des missions (tri par date DESC)
- Association mission ↔ détections pour traçabilité complète
- Gestion des images capturées par mission

---

### 🔍 Module Observation & Détection Drone
- CRUD des détections par mission (espèce, GPS lat/lon, comportement, timestamp, image)
- Analyse par lot d'images avec IAImageAnalyzer + YOLO
- Reconnaissance automatique de 12 espèces (Dauphin, Baleine, Tortue, Requin, Raie Manta, Méduse, Thon, Espadon, Phoque, Lion de Mer, Morse, Poisson Lune)
- Classification des comportements (Nage, Plongée, Repos, Alimentation, Migration, Jeu, Reproduction…)
- Score de confiance IA : Faible / Moyen / Élevé / Certain
- Rapport statistique : détections totales, individus, espèces uniques, détections haute confiance

---

### 🤖 Module IA & Prédiction d'Échouage
- CRUD des prédictions manuelles (zone, risque 1-10, espèces, température, météo, recommandations)
- Prédictions auto-générées par algorithme multi-facteurs (espèces, saisons, vent, vagues, sensibilité géographique)
- Alertes automatiques colorées : ROUGE (≥8) / ORANGE (5-7) / VERT (1-4)
- Analyse d'image par Ollama (LLM local, localhost:11434) avec encodage Base64
- Assistant conversationnel MARIA (OllamaChatService, DeepSeek deepseek-r1:7b) avec streaming temps réel
- Données météo temps réel par zone (ServiceMeteoAPI)

---

### 🗺️ Module Carte Interactive
- Affichage cartographique des zones et activités avec coordonnées LAT/LON
- Infrastructure GPS complète pour positionnement des détections drone
- Localisation des activités écologiques par zone

---

## Tech Stack

### Frontend
- Twig, Bootstrap
- Templatemo Elegance 528 (interface publique)
- Glass Admin 607 (interface admin)
- Leaflet.js (cartes interactives)
- Chart.js (visualisation des réservations)
- Web Speech API (recherche vocale)
- Symfony UX : Autocomplete, Chartjs, Turbo, Stimulus, React, Translator

### Backend
- Symfony 6, Doctrine ORM, MySQL
- Symfony Mailer, Form, Validator, Security, Monolog
- PHP-ML (KMeans, DecisionTree, Régression Linéaire, Z-Score)
- FOSElasticaBundle (Elasticsearch + fuzzy matching)
- KnpSnappy + Dompdf (PDF)
- PhpSpreadsheet (Excel)
- KnpPaginatorBundle (pagination)
- Sonata Exporter (export CSV/Excel)
- omines/antispam-bundle (anti-spam)

### IA & APIs
- Groq API `llama3-8b-8192` — rapports IA, emails, descriptions
- Ollama local `deepseek-r1:7b` — assistant MARIA + analyse image
- Python DecisionTree (`model.pkl`) — prédiction sous-remplissage activités
- Python TF-IDF — génération de descriptions d'activités
- Python YOLO — classification faune marine par image
- DeepFace — reconnaissance faciale
- API Ninjas — météo & trivia
- GeoIP — restriction géographique

### Services Python (Flask)
- `api.py` — vision/classification YOLO (`POST /predict`)
- `face_recognition_api.py` — reconnaissance faciale
- `predict.py` + `train_model.py` — entraînement et inférence DecisionTree
- `generate_description.py` — génération TF-IDF

### Outils
- WAMP (serveur local)
- Gmail SMTP / Mailtrap — notifications email
- Elasticsearch — recherche avancée

---

## Structure du projet
EcoMarineWeb/
├── assets/                        # JS, styles, React, Stimulus controllers
├── bin/                           # Console Symfony
├── config/                        # Bundles, packages, routes, services
├── migrations/                    # Migrations Doctrine
├── public/                        # Point d'entrée web, assets publics, uploads
├── python_ai/                     # YOLO (api.py, train.py, yolov8n-cls.pt)
├── python_service/                # Flask DeepFace (face_recognition_api.py)
├── src/
│   ├── Admin/
│   ├── Bundle/
│   ├── Command/                   # ActivityDnaCommand (app:ml:activity-dna)
│   ├── Controller/
│   ├── Entity/
│   ├── EventSubscriber/
│   ├── Form/
│   ├── Repository/
│   ├── Service/                   # GroqMailService, GroqReportService, OllamaChatService,
│   │                              # DescriptionAIService, PredictionAIService, WeatherService...
│   ├── Twig/
│   └── Kernel.php
├── templates/
│   ├── activite_ecologique/
│   ├── reservation/
│   ├── action_nettoyage/
│   ├── dechet/
│   ├── utilisateur/
│   ├── faune_marine/
│   ├── mission_drone/
│   ├── detection_drone/
│   ├── prediction_echouage/
│   ├── survzone/
│   ├── zonep/
│   ├── emails/
│   ├── admin/
│   ├── base.html.twig
│   └── base_admin.html.twig
├── translations/                  # messages.fr.yaml, messages.en.yaml
├── var/                           # Cache, logs, activity_dna.json
├── generate_description.py        # TF-IDF génération description
├── predict.py                     # Inférence DecisionTree
├── train_model.py                 # Entraînement modèle
├── requirements.txt
└── README.md
---


## Installation

```bash
git clone https://github.com/lina-ag/EcoMarineWeb.git
cd EcoMarineWeb
composer install
cp .env .env.local
# Renseigner DATABASE_URL, API_NINJAS_KEY, GROQ_API_KEY dans .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve
```

Services Python :
```bash
cd python_service
venv\Scripts\activate
python face_recognition_api.py

cd ../python_ai
python api.py

python predict.py
```

Commande ML DNA activités :
```bash
php bin/console app:ml:activity-dna
```

---

## Contributions

### Membres de l'équipe

| Membre | Module |
|--------|--------|
| [Lina Aguir](https://github.com/lina-ag) | Gestion des Activités & Réservations |
| [Nadine Hassini](https://github.com/nadine-hassini) | Gestion des Zones Protégées & Surveillances |
| [Teyssir Rhouma](https://github.com/teyssirhouma) | Gestion Utilisateurs |
| [Mohamed Amine Rhouma](https://github.com/MedAmine-Rh) | Gestion des Faunes Marines |
| [Mariem Farhat](https://github.com/MeryemFarhat) | Gestion des Déchets |
| [Mohamed Amine Sayadi](https://github.com/SAYADINOO) | Gestion des Bénévoles |

---

## Licence

Ce projet est sous licence MIT. Pour plus de détails, consultez le fichier [LICENSE](./LICENSE).

---

## Acknowledgments

This project was completed under the guidance of:

- **Mme Zeineb Gharsallah**
- **M. Massoudi Radhouane**
- **M. Jacem Mhenni**

Teaching team at **ESPRIM** — 3A3, 2025-2026.