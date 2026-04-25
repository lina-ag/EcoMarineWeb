@echo off
REM Script de démarrage du service de reconnaissance faciale Python (Windows)

cd /d "%~dp0"

REM Créer un environnement virtuel s'il n'existe pas
if not exist "venv" (
    echo Création de l'environnement virtuel...
    python -m venv venv
)

REM Activer l'environnement virtuel
call venv\Scripts\activate.bat

REM Installer les dépendances
echo Installation des dépendances...
python -m pip install --upgrade pip
pip install -r requirements.txt

REM Démarrer le service
echo Démarrage du service de reconnaissance faciale...
set FLASK_PORT=5000
python face_recognition_api.py

pause
