# 🎯 EcoMarine - Système de Reconnaissance Faciale avec OpenCV

## 📋 Résumé

Ce guide explique comment configurer et utiliser le système de reconnaissance faciale basé sur **OpenCV** et **Python** pour :

- ✅ **Sign Up** : Enregistrement du visage lors de l'inscription (chercheurs)
- ✅ **Sign In** : Connexion par reconnaissance faciale sans mot de passe

## 🏗️ Architecture

```
┌──────────────────────────────────────────────┐
│  Frontend (Navigateur Web)                   │
│  - Capture vidéo caméra                      │
│  - Affichage interface interactive           │
└────────────────┬─────────────────────────────┘
                 │ Base64 Image (HTTP)
                 ▼
┌──────────────────────────────────────────────┐
│  Backend Symfony (PHP)                       │
│  - Gestion utilisateurs                      │
│  - Routes Web (/signUp/face, /signIn/face)  │
│  - Communication API Python                  │
└────────────────┬─────────────────────────────┘
                 │ JSON (HTTP)
                 ▼
┌──────────────────────────────────────────────┐
│  Python Face Recognition API (Flask)        │
│  - Détection visage (HOG/CNN)               │
│  - Extraction encodage (128D)               │
│  - Comparaison visages (distance euclidienne)│
│  - Stockage images temporaires               │
└──────────────────────────────────────────────┘
```

## 🚀 Installation et Démarrage Rapide

### Étape 1️⃣ : Installer le Service Python

**Sur Windows :**
```bash
cd python_service
python -m venv venv
venv\Scripts\activate.bat
pip install -r requirements.txt
```

**Sur Linux/Mac :**
```bash
cd python_service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### Étape 2️⃣ : Démarrer le Service Python

**Windows :**
```bash
cd python_service
start.bat
```

**Linux/Mac :**
```bash
cd python_service
bash start.sh
```

✅ Le service sera accessible à : **http://localhost:5000**

Vérification :
```bash
curl http://localhost:5000/health
```

### Étape 3️⃣ : Démarrer Symfony

```bash
symfony serve
# ou
php -S localhost:8000 -t public
```

### Étape 4️⃣ : Tester le Système

1. Allez sur [http://localhost:8000/signUp](http://localhost:8000/signUp)
2. Créez un compte en tant que **chercheur**
3. Vous serez redirigé automatiquement vers l'enregistrement facial
4. Autorisez l'accès à la caméra et capturez votre visage
5. Une fois enregistré, testez la connexion avec `/signIn` → "Se connecter avec visage"

## 📁 Fichiers Créés/Modifiés

### Nouveaux Fichiers

```
python_service/
├── face_recognition_api.py      # API Flask principale
├── requirements.txt              # Dépendances Python
├── start.bat                     # Script démarrage Windows
├── start.sh                      # Script démarrage Linux/Mac
└── README.md                     # Documentation Python

src/Controller/
└── DiagnosticController.php      # Routes de diagnostic

templates/diagnostic/
└── face_recognition.html.twig    # Page diagnostic

FACE_RECOGNITION_GUIDE.md         # Guide détaillé
.env.local                        # Variables d'environnement
```

### Fichiers Modifiés

```
src/Service/
└── FaceRecognitionService.php    # Appels API Python

src/Controller/
├── SecurityController.php        # Redirection post-inscription
└── FaceRecognitionController.php # Endpoints reconnaissance faciale

templates/security/
├── signUp.html.twig             # Formulaire inscription
└── face_register.html.twig       # Page enregistrement facial

config/
└── services.yaml                 # Configuration services
```

## 🔧 Configuration

### Variables d'Environnement (.env.local)

```env
# URL du service Python
PYTHON_FACE_SERVICE_URL=http://localhost:5000

# Timeout API
PYTHON_FACE_SERVICE_TIMEOUT=30
```

### Services Symfony (config/services.yaml)

```yaml
parameters:
    python_face_service_url: 'http://localhost:5000'

services:
    App\Service\FaceRecognitionService:
        arguments:
            $pythonServiceUrl: '%python_face_service_url%'
```

## 📊 Flux d'Utilisation

### Inscription (Sign Up)

```
1. Utilisateur → /signUp
2. Remplit formulaire (nom, email, rôle=chercheur, etc)
3. Clique "S'inscrire"
4. Backend crée utilisateur en base
5. Redirection → /signUp/face
6. Frontend affiche caméra
7. Utilisateur autorise accès caméra
8. Capture visage → POST /face/register
9. Backend appelle API Python (/extract_encoding)
10. Python détecte visage → extrait encodage 128D
11. Backend stocke encodage en base (Utilisateur.face_encoding)
12. Redirection → /signIn
```

### Connexion (Sign In)

```
1. Utilisateur → /signIn
2. Clique "Se connecter avec visage"
3. Redirection → /signIn/face
4. Frontend affiche caméra
5. Utilisateur capture visage
6. POST /face/recognize
7. Backend appelle API Python (/extract_encoding)
8. Python extrait encodage du visage capturé
9. Backend boucle sur TOUS les utilisateurs avec face_encoding
10. Pour chaque utilisateur, appelle API Python (/compare_faces)
11. Python compare encodages → distance euclidienne
12. Si distance < 0.6 → MATCH
13. Connexion utilisateur + redirection
```

## 🎨 API Python - Endpoints

### 1️⃣ Health Check

```http
GET /health
```

**Réponse :**
```json
{
    "status": "ok",
    "service": "Face Recognition API",
    "version": "1.0.0"
}
```

### 2️⃣ Extraire l'Encodage Facial

```http
POST /extract_encoding
Content-Type: application/json

{
    "image": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Réponse (Succès) :**
```json
{
    "success": true,
    "message": "Encodage facial extrait avec succès",
    "encoding": [0.123, -0.456, 0.789, ...],
    "faces_count": 1
}
```

**Réponse (Erreur) :**
```json
{
    "success": false,
    "message": "Plusieurs visages détectés (2)",
    "encoding": null,
    "faces_count": 2
}
```

### 3️⃣ Comparer Deux Visages

```http
POST /compare_faces
Content-Type: application/json

{
    "encoding1": [0.123, -0.456, ...],
    "encoding2": [0.789, -0.012, ...]
}
```

**Réponse :**
```json
{
    "success": true,
    "match": true,
    "distance": 0.35,
    "similarity_percentage": 82.5,
    "threshold": 0.6
}
```

### 4️⃣ Vérifier Présence Visage

```http
POST /verify_face
Content-Type: application/json

{
    "image": "data:image/jpeg;base64,..."
}
```

**Réponse :**
```json
{
    "success": true,
    "face_detected": true,
    "face_count": 1,
    "message": "Visage détecté avec succès"
}
```

## ⚡ Performance

| Opération | Temps |
|-----------|-------|
| Détection visage (HOG) | ~100-200ms |
| Extraction encodage | ~100-200ms |
| Comparaison (2 visages) | <1ms |
| **Total par image** | **~200-400ms** |

*Première requête : +2-3s (chargement modèles)*

## 🔐 Sécurité

✅ **Encodages stockés** (128D vectors) - PAS d'images sauvegardées
✅ **Impossible de retrouver le visage** à partir de l'encodage
✅ **Validation serveur** - Vérification 1 seul visage par image
✅ **HTTPS en production** - Chiffrage des communications

⚠️ **À faire en production :**
- Utiliser HTTPS
- Mettre en place authentification API
- Limiter les requêtes par IP
- Auditer les logs d'accès

## 🐛 Troubleshooting

### ❌ Service Python ne démarre pas

```bash
cd python_service
python -m venv venv
venv\Scripts\activate.bat
pip install -r requirements.txt
python face_recognition_api.py
```

### ❌ Erreur "No module named 'dlib'"

**Windows :**
1. Installer Visual Studio Build Tools
2. Réinstaller dlib :
```bash
pip install --force-reinstall dlib
```

**Linux :**
```bash
sudo apt-get install build-essential cmake
pip install --force-reinstall dlib
```

**Mac :**
```bash
brew install cmake
pip install --force-reinstall dlib
```

### ❌ La reconnaissance faciale ne fonctionne pas

1. **Vérifier service Python :**
```bash
curl http://localhost:5000/health
```

2. **Vérifier les logs Symfony :**
```bash
tail -f var/log/dev.log
```

3. **Tester l'API directement** (avec Postman)

4. **Vérifier permissions caméra** dans navigateur

### ❌ Reconnaissance très lente

- **Première requête** : ~2-3s normal (chargement modèles)
- **Requêtes suivantes** : ~200-400ms normal
- **Trop lent ?** Vérifier charge CPU/RAM

### ❌ Trop de faux positifs

Réduire le seuil dans `python_service/face_recognition_api.py` :
```python
FACE_DISTANCE_THRESHOLD = 0.5  # Plus strict (défaut: 0.6)
```

## 📖 Pages de Diagnostic

- **Health Check API** : [/diagnostic/health](/diagnostic/health)
- **Diagnostic complet** : [/diagnostic/face-recognition](/diagnostic/face-recognition)
- **Formulaire inscription** : [/signUp](/signUp)
- **Connexion** : [/signIn](/signIn)

## 🚀 Déploiement en Production

### Docker (optionnel)

**Dockerfile :**
```dockerfile
FROM python:3.10-slim
WORKDIR /app
COPY python_service/requirements.txt .
RUN apt-get update && apt-get install -y build-essential cmake
RUN pip install -r requirements.txt
COPY python_service/face_recognition_api.py .
EXPOSE 5000
CMD ["gunicorn", "--workers", "4", "--bind", "0.0.0.0:5000", "face_recognition_api:app"]
```

**Lancer avec Docker Compose :**
```bash
docker-compose up -d
```

### Sans Docker (Gunicorn)

```bash
cd python_service
source venv/bin/activate  # ou venv\Scripts\activate
gunicorn --workers 4 --bind 0.0.0.0:5000 face_recognition_api:app
```

### Nginx (Reverse Proxy)

```nginx
server {
    listen 80;
    server_name api.example.com;

    location / {
        proxy_pass http://localhost:5000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 📚 Ressources

- [face_recognition (PyPI)](https://github.com/ageitgey/face_recognition)
- [OpenCV Documentation](https://docs.opencv.org/)
- [dlib Documentation](http://dlib.net/)
- [Flask Documentation](https://flask.palletsprojects.com/)

## ✅ Checklist

- [ ] Service Python installé et lancé
- [ ] Curl /health retourne "ok"
- [ ] Symfony démarre sans erreur
- [ ] Page /signUp accessible
- [ ] Inscription chercheur fonctionnelle
- [ ] Enregistrement facial fonctionnel
- [ ] Connexion faciale fonctionnelle
- [ ] Diagnostic page ok (/diagnostic/face-recognition)

## 📞 Support

En cas de problème :
1. Vérifier les logs
2. Tester l'API Python avec curl/Postman
3. Vérifier permissions caméra
4. Redémarrer les services

---

**Version** : 1.0.0  
**Dernière mise à jour** : 19 Avril 2026
