@echo off
REM Script de demarrage du service de reconnaissance faciale Python (Windows)

cd /d "%~dp0"

REM Creer un environnement virtuel s'il n'existe pas ou s'il est incomplet
if not exist ".venv\Scripts\activate.bat" (
    echo Creation de l'environnement virtuel...
    python -m venv .venv
)

REM Activer l'environnement virtuel
call .venv\Scripts\activate.bat

REM Installer les dependances
echo Installation des dependances...
python -m pip install --upgrade pip
pip install -r requirements.txt

REM Demarrer le service
echo Demarrage du service de reconnaissance faciale...
set FLASK_PORT=5000
set PYTHONUTF8=1
python face_recognition_api.py

pause
