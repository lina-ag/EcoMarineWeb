# Carte Interactive de Prédiction d'Échouage

## Vue d'ensemble

La carte interactive permet aux utilisateurs de sélectionner une zone côtière sur une carte et d'obtenir une prédiction intelligente du risque d'échouage des mammifères marins. Le système utilise l'IA pour analyser les conditions météorologiques actuelles et historiques afin de calculer un niveau de risque.

## Fonctionnalités

### 🗺️ **Carte Interactive**
- Carte basée sur OpenStreetMap (alternative open source à Google Maps)
- Marqueurs pour les 8 zones côtières tunisiennes
- Sélection de zone par clic sur la carte ou via un menu déroulant
- Affichage des coordonnées GPS en temps réel

### 🌤️ **Données Météorologiques**
- Récupération automatique des conditions météorologiques actuelles
- Intégration avec l'API OpenWeatherMap
- Analyse des facteurs météorologiques :
  - Température de l'eau
  - Conditions générales (ensoleillé, pluie, vent, etc.)
  - Vitesse du vent
  - Humidité et pression atmosphérique

### 🧠 **Agent de Prédiction IA**
- Calcul automatique du niveau de risque (1-5)
- Analyse de multiples facteurs :
  - Conditions météorologiques
  - Saisonnalité
  - Observations historiques
  - Température de l'eau
- Génération de recommandations personnalisées

### 💾 **Sauvegarde des Prédictions**
- Stockage automatique des prédictions en base de données
- Intégration avec le système existant de gestion des prédictions
- Historique des analyses par zone et date

## Installation et Configuration

### 1. **Clé API OpenWeatherMap**
Obtenez une clé API gratuite sur [OpenWeatherMap](https://openweathermap.org/api).

Ajoutez votre clé dans le fichier `.env` :
```env
OPENWEATHER_API_KEY=votre_cle_api_ici
```

### 2. **Installation des dépendances**
```bash
composer install
npm install  # si nécessaire pour les assets front-end
```

### 3. **Mise à jour de la base de données**
```bash
php bin/console doctrine:migrations:migrate
```

### 4. **Configuration des services**
Les services sont automatiquement configurés via l'injection de dépendances Symfony.

## Utilisation

### Accès à la carte
1. Connectez-vous à l'interface d'administration
2. Naviguez vers "Marine" > "Carte IA" dans le menu latéral
3. La carte de la Tunisie s'affiche avec les zones côtières

### Réalisation d'une prédiction
1. **Sélectionnez une zone** :
   - Cliquez directement sur un marqueur de zone
   - Ou utilisez le menu déroulant "Zone sélectionnée"

2. **Vérifiez les coordonnées** :
   - Les coordonnées GPS s'affichent automatiquement
   - Vous pouvez les ajuster manuellement si nécessaire

3. **Choisissez la date** :
   - La date du jour est sélectionnée par défaut
   - Vous pouvez choisir une date future pour une prédiction anticipée

4. **Lancez l'analyse** :
   - Cliquez sur "Analyser le risque"
   - L'agent IA analyse les données météorologiques et calcule le risque

5. **Consultez les résultats** :
   - Niveau de risque sur 5 (avec code couleur)
   - Conditions météorologiques actuelles
   - Facteurs pris en compte
   - Recommandations personnalisées

6. **Sauvegardez la prédiction** :
   - Cliquez sur "Sauvegarder la prédiction"
   - La prédiction est enregistrée et accessible via "Prédictions"

## Architecture Technique

### Services
- **`WeatherService`** : Gestion des appels API météorologiques
- **`PredictionService`** : Logique de calcul des prédictions

### Contrôleur
- **`PredictionMapController`** : Gestion des routes et interactions AJAX

### API Endpoints
- `GET /prediction/map/zones` : Liste des zones côtières
- `GET /prediction/map/weather/{lat}/{lon}` : Données météo pour des coordonnées
- `POST /prediction/map/predict` : Calcul d'une prédiction
- `POST /prediction/map/save` : Sauvegarde d'une prédiction

### Technologies Frontend
- **Leaflet.js** : Bibliothèque de cartographie open source
- **OpenStreetMap** : Fonds de carte gratuit
- **JavaScript ES6** : Interactions dynamiques
- **AJAX/Fetch API** : Communications avec le backend

## Algorithme de Prédiction

Le niveau de risque est calculé selon cette formule :

```
Risque de base = 1
+ Température de l'eau (< 10°C : +2, < 15°C : +1)
+ Conditions météo (Tempête: +3, Pluie: +1, Vent: +2)
+ Saison (Hiver: +1)
+ Observations récentes (+0.5)
+ Vent fort (> 20 m/s : +1)

Risque final = max(1, min(5, arrondi(risque_calculé)))
```

## Zones Côtières Définies

Le système reconnaît 8 zones côtières tunisiennes :
- Nord (37.0°N, 10.0°E)
- Nord-Est (36.8°N, 10.5°E)
- Est (36.5°N, 10.8°E)
- Sud-Est (35.8°N, 10.6°E)
- Sud (33.8°N, 10.1°E)
- Sud-Ouest (34.2°N, 9.8°E)
- Ouest (35.5°N, 9.5°E)
- Nord-Ouest (36.2°N, 9.8°E)

## Sécurité et Performance

- **Rate limiting** : Protection contre les abus d'API
- **Cache** : Mise en cache des données météorologiques
- **Validation** : Contrôle strict des données d'entrée
- **Logging** : Suivi des erreurs et performances

## Dépannage

### Problèmes courants

1. **Carte ne s'affiche pas**
   - Vérifiez la connexion internet
   - Vérifiez que Leaflet.js est chargé

2. **Données météo non disponibles**
   - Vérifiez la clé API OpenWeatherMap
   - Vérifiez les quotas d'utilisation

3. **Erreur lors de la sauvegarde**
   - Vérifiez les permissions base de données
   - Vérifiez que Doctrine est configuré correctement

### Logs utiles
```bash
# Logs des services
tail -f var/log/dev.log

# Logs Doctrine
php bin/console doctrine:schema:validate
```

## Évolutions Futures

- Intégration de données océanographiques (courants, marées)
- Historique des prédictions par zone
- Alertes automatiques pour risques élevés
- Interface mobile optimisée
- Intégration de données satellites

---

*Cette fonctionnalité transforme l'approche traditionnelle des prédictions en une expérience interactive et intelligente, permettant une prise de décision plus rapide et plus précise pour la protection des mammifères marins.*