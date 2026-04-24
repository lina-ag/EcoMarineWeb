#!/bin/bash
# Script de démarrage du service de reconnaissance faciale Python

cd "$(dirname "$0")"

# Créer un environnement virtuel s'il n'existe pas
if [ ! -d "venv" ]; then
    echo "Création de l'environnement virtuel..."
    python3 -m venv venv
fi

# Activer l'environnement virtuel
source venv/bin/activate

# Installer les dépendances
echo "Installation des dépendances..."
pip install --upgrade pip
pip install -r requirements.txt

# Démarrer le service
echo "Démarrage du service de reconnaissance faciale..."
export FLASK_PORT=${FLASK_PORT:-5000}
python3 face_recognition_api.py
