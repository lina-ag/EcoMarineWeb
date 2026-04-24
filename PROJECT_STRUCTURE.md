# 📁 Structure Complète du Projet

```
EcoMarineWeb/
│
├── 🔵 DOCUMENTATION (Nouveau)
│   ├── QUICK_START.md                      ← ⭐ COMMENCER ICI
│   ├── README_FACE_RECOGNITION.md          (Guide complet)
│   ├── FACE_RECOGNITION_GUIDE.md          (Architecture)
│   └── CHANGES_SUMMARY.md                 (Résumé changements)
│
├── 🐍 SERVICE PYTHON (Nouveau)
│   └── python_service/
│       ├── face_recognition_api.py        (API Flask OpenCV)
│       ├── requirements.txt               (Dépendances)
│       ├── start.bat                     (Script Windows)
│       ├── start.sh                      (Script Linux/Mac)
│       ├── README.md                     (Docs API)
│       └── venv/                         (Virtual env)
│
├── 🔧 SYMFONY BACKEND
│   ├── src/
│   │   ├── Controller/
│   │   │   ├── SecurityController.php         ✏️ MODIFIÉ
│   │   │   ├── FaceRecognitionController.php  ✏️ MODIFIÉ
│   │   │   └── DiagnosticController.php       ✨ NOUVEAU
│   │   │
│   │   ├── Service/
│   │   │   └── FaceRecognitionService.php     ✏️ MODIFIÉ
│   │   │                                      (Appels API Python)
│   │   │
│   │   ├── Entity/
│   │   │   └── Utilisateur.php               (face_encoding, face_image)
│   │   │
│   │   └── Repository/
│   │       └── UtilisateurRepository.php     (findAllWithFaceEncoding)
│   │
│   ├── config/
│   │   ├── services.yaml                    ✏️ MODIFIÉ
│   │   └── packages/                        (symfony configs)
│   │
│   ├── templates/
│   │   ├── security/
│   │   │   ├── signIn.html.twig            (avec bouton visage)
│   │   │   ├── signUp.html.twig            ✏️ MODIFIÉ
│   │   │   ├── face_login.html.twig        (interface caméra)
│   │   │   └── face_register.html.twig     ✨ NOUVEAU
│   │   │
│   │   ├── diagnostic/
│   │   │   └── face_recognition.html.twig  ✨ NOUVEAU
│   │   │                                    (Page diagnostic)
│   │   │
│   │   └── base.html.twig
│   │
│   ├── public/
│   │   ├── index.php
│   │   └── uploads/
│   │       └── faces/                       (Images temporaires)
│   │
│   ├── var/
│   │   ├── cache/
│   │   └── log/
│   │       └── dev.log                     (Logs erreurs)
│   │
│   ├── .env.local                          ✨ NOUVEAU
│   │                                       (Variables env)
│   │
│   ├── composer.json                       (PHP deps)
│   └── composer.lock
│
├── 📦 NODE/FRONT
│   └── assets/
│       ├── css/
│       ├── js/
│       └── vendor/
│
├── 🗄️ DATABASE
│   └── migrations/
│       └── Version*.php
│
├── 🧪 TESTS
│   └── tests/
│
├── 📋 ROOT FILES
│   ├── compose.yaml
│   ├── phpunit.xml.dist
│   └── README.md

```

## 🔄 Communication Entre Services

```
┌─────────────────────────┐
│   Browser Frontend      │
│                         │
│  [Caméra]             │
│   ↓                    │
│  Canvas → Base64       │
│   ↓                    │
│  Fetch POST            │
└────────────┬───────────┘
             │ HTTP JSON
             │ (image base64)
             ↓
┌─────────────────────────────────────┐
│   Symfony Backend (PHP)             │
│                                     │
│  SecurityController               │
│  FaceRecognitionController        │
│  FaceRecognitionService           │
│                                     │
│  HttpClient::request()            │
│   ↓                                │
│  POST http://localhost:5000      │
└────────────┬─────────────────────┘
             │ HTTP JSON
             │ (encoding)
             ↓
┌─────────────────────────────────────┐
│   Python API (Flask)                │
│                                     │
│  /extract_encoding                │
│  /compare_faces                   │
│  /verify_face                     │
│                                     │
│  face_recognition library         │
│  → face_locations()               │
│  → face_encodings()               │
│  → np.linalg.norm()               │
└─────────────────────────────────────┘
```

## 🚀 Démarrage Services

```bash
# Terminal 1: Python Service
cd python_service
start.bat              # Windows
# ou
bash start.sh          # Linux/Mac
# Affiche: Running on http://0.0.0.0:5000

# Terminal 2: Symfony
symfony serve
# Affiche: http://localhost:8000

# Terminal 3: Tests
curl http://localhost:5000/health
curl http://localhost:8000/signUp
```

## 🔐 Flux de Données

### Inscription
```
Utilisateur
    ↓
Form /signUp (nom, email, rôle=chercheur, password)
    ↓
POST /signUp
    ↓
SecurityController::signUp()
    ↓
Utilisateur créé en BDD + password hashé
    ↓
Session[user_id_for_face_registration] = user->getId()
    ↓
Redirect /signUp/face
    ↓
face_register.html.twig (caméra)
    ↓
User capture visage
    ↓
POST /face/register (image base64)
    ↓
FaceRecognitionController::registerFace()
    ↓
FaceRecognitionService::processCapturedImage()
    ↓
API Python: POST /extract_encoding
    ↓
[0.123, -0.456, 0.789, ...]  ← Encodage 128D
    ↓
Utilisateur.face_encoding = base64(json_encode([...]))
    ↓
Sauvegarde BDD
    ↓
Session::remove('user_id_for_face_registration')
    ↓
✅ Utilisateur enregistré avec visage
```

### Connexion
```
Utilisateur
    ↓
GET /signIn/face
    ↓
face_login.html.twig (caméra)
    ↓
User scanne visage
    ↓
POST /face/recognize (image base64)
    ↓
FaceRecognitionController::recognize()
    ↓
FaceRecognitionService::processCapturedImage()
    ↓
API Python: POST /extract_encoding
    ↓
Encodage utilisateur = [0.789, -0.456, ...]
    ↓
Loop foreach Utilisateur with face_encoding
    ↓
API Python: POST /compare_faces
      → encoding_utilisateur vs encoding_BDD
      → distance = 0.35
      → similarity_percentage = 82.5%
    ↓
If distance < 0.6 → MATCH
    ↓
Session[user] = $bestMatch
    ↓
Redirect selon role (admin ou user)
    ↓
✅ Utilisateur connecté
```

## 📊 Base de Données

### Table: utilisateur

```sql
CREATE TABLE utilisateur (
    id_utilisateur INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(180) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    face_encoding LONGBLOB,              ← Encodage 128D
    face_image VARCHAR(255),             ← Chemin image
    date_naissance DATE NOT NULL,
    id_role INT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (id_role) REFERENCES role(id_role)
);
```

## 🎯 Routes Disponibles

### Frontend
| Route | Method | Description |
|-------|--------|-------------|
| `/signUp` | GET | Formulaire inscription |
| `/signUp` | POST | Créer compte |
| `/signUp/face` | GET | Enregistrement facial |
| `/signIn` | GET/POST | Connexion classique |
| `/signIn/face` | GET | Interface connexion faciale |

### API Backend
| Route | Method | Description |
|-------|--------|-------------|
| `/face/capture` | POST | Capturer image (temp) |
| `/face/register` | POST | Enregistrer visage |
| `/face/recognize` | POST | Reconnaître visage |
| `/diagnostic/health` | GET | Vérifier service |
| `/diagnostic/face-recognition` | GET | Page diagnostic |

### API Python
| Route | Method | Description |
|-------|--------|-------------|
| `/health` | GET | Health check |
| `/extract_encoding` | POST | Extraire encodage |
| `/compare_faces` | POST | Comparer deux visages |
| `/verify_face` | POST | Vérifier 1 visage |

## 💾 Stockage

### Base de Données
- `Utilisateur.face_encoding` : JSON encodé en base64 (128 nombres)
- `Utilisateur.face_image` : Chemin vers image (optionnel)

### Fichiers Système
- `public/uploads/faces/` : Images temporaires (upload)
- `python_service/venv/` : Environnement virtuel Python
- `var/log/dev.log` : Logs Symfony

## 🔒 Sécurité

```
Client           →    Server           →    Python
(Image raw)           (Validation)           (Traitement)
                      (Normalization)       (Encodage)
                      (Storage)             (Comparaison)
                      (HTTPS en prod)       (HTTPS en prod)
```

## 📊 Performance

- **Détection** : 100-200ms
- **Encodage** : 100-200ms  
- **Comparaison** : <1ms
- **Total** : 200-400ms (+ 2-3s première requête)

---

**Vous êtes prêt à commencer !** 🎉
Consultez **QUICK_START.md** pour démarrer.
