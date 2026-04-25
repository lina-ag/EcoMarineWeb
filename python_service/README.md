# EcoMarine - Service de Reconnaissance Faciale avec OpenCV

## Installation et Démarrage

### Prérequis
- Python 3.7+
- pip

### Installation (Première fois)

#### Sur Windows :
```bash
cd python_service
python -m venv venv
venv\Scripts\activate.bat
pip install -r requirements.txt
```

#### Sur Linux/Mac :
```bash
cd python_service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### Démarrage du Service

#### Windows :
```bash
cd python_service
start.bat
```

#### Linux/Mac :
```bash
cd python_service
bash start.sh
```

Le service sera disponible à : **http://localhost:5000**

### Endpoints API

#### 1. Health Check
```
GET /health
```
Vérifie que le service est en ligne.

#### 2. Extraire l'encodage facial
```
POST /extract_encoding
Content-Type: application/json

{
    "image": "data:image/jpeg;base64,..."
}
```

**Réponse (succès):**
```json
{
    "success": true,
    "message": "Encodage facial extrait avec succès",
    "encoding": [0.123, -0.456, ...],
    "faces_count": 1
}
```

#### 3. Comparer deux visages
```
POST /compare_faces
Content-Type: application/json

{
    "encoding1": [0.123, -0.456, ...],
    "encoding2": [0.789, -0.012, ...]
}
```

**Réponse:**
```json
{
    "success": true,
    "match": true,
    "distance": 0.45,
    "similarity_percentage": 87.5,
    "threshold": 0.6
}
```

#### 4. Vérifier si l'image contient un visage
```
POST /verify_face
Content-Type: application/json

{
    "image": "data:image/jpeg;base64,..."
}
```

### Configuration

Le service utilise les paramètres suivants :
- **Port** : 5000 (modifiable avec `FLASK_PORT`)
- **Seuil de similarité** : 0.6 (modifiable dans le code)
- **Modèle de détection** : HOG (rapide) - peut être changé en 'cnn' pour plus de précision

### Dépendances

- **face_recognition** : Reconnaissance faciale de haute qualité
- **opencv-python** : Traitement d'images
- **dlib** : Détection et encodage facial
- **numpy** : Opérations numériques
- **Flask** : API REST
- **Pillow** : Manipulation d'images

### Troubleshooting

#### Erreur : "No module named 'dlib'"
Installez les dépendances C++ requises :
- **Windows** : Téléchargez Visual Studio Build Tools
- **Linux** : `sudo apt-get install build-essential cmake`
- **Mac** : `brew install cmake`

Puis réinstallez :
```bash
pip install --force-reinstall dlib
```

#### Lenteur de la première requête
C'est normal, la première requête charge les modèles en mémoire (prend ~2-3 secondes).

### Configuration pour Production

Pour un environnement de production, utilisez gunicorn :
```bash
gunicorn --workers 4 --bind 0.0.0.0:5000 face_recognition_api:app
```
