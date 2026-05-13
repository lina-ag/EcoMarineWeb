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
| Nadine Hassini | (https://github.com/nadine-hassini)  | GESTION DES ZONES PROTEGÉES & SURVEILLANCES |
| Teyssir Rhouma | (https://github.com/teyssirhouma) | GESTION UTILISATEUR | 
| Mohamed Amine Rhouma | (https://github.com/MedAmine-Rh)  | GESTION DES FAUNES MARINES|
| Mariem Farhat | (https://github.com/MeryemFarhat) | gestion des déchets
| Mohamed Amine Sayadi | (https://github.com/SAYADINOO) | Gestion des bénévoles |

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