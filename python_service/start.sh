#!/bin/bash
# Script de demarrage du service de reconnaissance faciale Python (Linux/Mac)

cd "$(dirname "$0")"

# Creer un environnement virtuel s'il n'existe pas ou s'il est incomplet
if [ ! -f ".venv/bin/activate" ]; then
    echo "Creation de l'environnement virtuel..."
    python3 -m venv .venv
fi

# Activer l'environnement virtuel
source .venv/bin/activate

# Installer les dependances
echo "Installation des dependances..."
pip install --upgrade pip
pip install -r requirements.txt

# Demarrer le service
echo "Demarrage du service de reconnaissance faciale..."
export FLASK_PORT=5000
export PYTHONUTF8=1
python3 face_recognition_api.py
