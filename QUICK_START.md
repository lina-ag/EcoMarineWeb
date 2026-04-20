# 🚀 QUICK START - Reconnaissance Faciale avec OpenCV

## ⚡ 5 minutes pour tout mettre en place

### Terminal 1️⃣ : Démarrer Python

```bash
cd python_service

# Windows
start.bat

# Linux/Mac
bash start.sh
```

✅ Le service démarre et affiche :
```
 * Running on http://0.0.0.0:5000
```

### Terminal 2️⃣ : Démarrer Symfony

```bash
symfony serve

# ou

php -S localhost:8000 -t public
```

### Browser 🌐 : Tester

1. **Vérifier que le service Python fonctionne :**
   - Ouvrez [http://localhost:5000/health](http://localhost:5000/health)
   - Vous devriez voir : `{"status":"ok",...}`

2. **Tester l'inscription faciale :**
   - Allez sur [http://localhost:8000/signUp](http://localhost:8000/signUp)
   - Remplissez le formulaire
   - Choisissez rôle = **chercheur**
   - Cliquez "S'inscrire"
   - Autorisez l'accès à la caméra
   - Capturez votre visage

3. **Tester la connexion faciale :**
   - Allez sur [http://localhost:8000/signIn](http://localhost:8000/signIn)
   - Cliquez "Se connecter avec visage"
   - Scannez votre visage
   - ✅ Vous devriez être connecté !

## 🔍 Vérifier que tout fonctionne

```bash
# Test API Python
curl http://localhost:5000/health

# Test page Symfony
curl http://localhost:8000/signUp

# Test diagnostic
curl http://localhost:8000/diagnostic/health
```

## 📋 Flux Rapide

```
Inscription avec Visage :
  /signUp 
    → Créer compte chercheur
    → Redirection /signUp/face
    → Capturer visage
    → Sauvegarde encodage

Connexion avec Visage :
  /signIn/face
    → Scanner visage
    → Comparer avec base
    → Connexion automatique
```

## ⚙️ Configuration (Optionnel)

Modifier le port Python dans `.env.local` :
```env
PYTHON_FACE_SERVICE_URL=http://localhost:5001
```

Modifier le seuil de similarité dans `python_service/face_recognition_api.py` :
```python
FACE_DISTANCE_THRESHOLD = 0.5  # Plus strict (défaut: 0.6)
```

## 🐛 Problèmes Courants

| Problème | Solution |
|----------|----------|
| Port 5000 déjà utilisé | `lsof -i :5000` et killpid, ou modifier port |
| Erreur dlib | `pip install --force-reinstall dlib` |
| Caméra ne marche pas | Autoriser accès caméra dans navigateur |
| Reconnaissance ne marche pas | Vérifier que `http://localhost:5000/health` répond |

## 📚 Docs Complètes

- **[README_FACE_RECOGNITION.md](README_FACE_RECOGNITION.md)** - Documentation complète
- **[FACE_RECOGNITION_GUIDE.md](FACE_RECOGNITION_GUIDE.md)** - Guide détaillé
- **[python_service/README.md](python_service/README.md)** - Docs API Python
- **[/diagnostic/face-recognition](/diagnostic/face-recognition)** - Page diagnostic en ligne

---

**C'est tout ! 🎉** Vous pouvez maintenant tester la reconnaissance faciale !
