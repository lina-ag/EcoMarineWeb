import mysql.connector
import pandas as pd
from sklearn.tree import DecisionTreeClassifier
from sklearn.preprocessing import LabelEncoder
import pickle
import json
from pathlib import Path
import sklearn

base_dir = Path(__file__).resolve().parent

# ─── 1. CONNEXION BASE DE DONNÉES ───────────────────────────────────────────
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="ecomarine"
)

# ─── 2. CHARGEMENT DES DONNÉES ──────────────────────────────────────────────
query = """
    SELECT 
        a.id_activite,
        a.nom_activite,
        a.date_activite,
        a.capacite,
        COALESCE(SUM(r.nombre_personnes), 0) AS total_personnes
    FROM activite_ecologique a
    LEFT JOIN reservation r ON r.id_activite = a.id_activite
    GROUP BY a.id_activite, a.nom_activite, a.date_activite, a.capacite
"""

df = pd.read_sql(query, conn)
conn.close()

print(f"✅ {len(df)} activités chargées depuis la base")

# ─── 3. FEATURE ENGINEERING ─────────────────────────────────────────────────
df['date_activite'] = pd.to_datetime(df['date_activite'])
df['mois']          = df['date_activite'].dt.month
df['jour_semaine']  = df['date_activite'].dt.dayofweek  # 0=Lundi, 6=Dimanche
df['taux_remplissage'] = df['total_personnes'] / df['capacite']

# Extraire le type d'activité depuis le nom (premier mot)
df['type_activite'] = df['nom_activite'].str.split().str[0].str.lower()

# ─── 4. TARGET : sous-remplie si taux < 50% ─────────────────────────────────
df['sous_remplie'] = (df['taux_remplissage'] < 0.5).astype(int)
# 1 = risque sous-remplie, 0 = bien remplie

print(f"   → Activités à risque     : {df['sous_remplie'].sum()}")
print(f"   → Activités bien remplies: {(df['sous_remplie'] == 0).sum()}")

# ─── 5. ENCODAGE ─────────────────────────────────────────────────────────────
le = LabelEncoder()
df['type_activite_enc'] = le.fit_transform(df['type_activite'])

# ─── 6. FEATURES & ENTRAÎNEMENT ──────────────────────────────────────────────
features = ['mois', 'jour_semaine', 'capacite', 'type_activite_enc']
X = df[features]
y = df['sous_remplie']

model = DecisionTreeClassifier(max_depth=4, random_state=42)
model.fit(X, y)

print(f"✅ Modèle entraîné avec {len(features)} features")

# ─── 7. SAUVEGARDE ───────────────────────────────────────────────────────────
with open(base_dir / 'model.pkl', 'wb') as f:
    pickle.dump(model, f)

with open(base_dir / 'label_encoder.pkl', 'wb') as f:
    pickle.dump(le, f)

print("✅ model.pkl et label_encoder.pkl sauvegardés")

# Save model metadata (feature names, sklearn version) to help predict.py
meta = {
    'features': features,
    'sklearn_version': getattr(sklearn, '__version__', None),
    'demand_stats': {
        'global_avg': round(float(df['total_personnes'].mean()), 2),
        'type_avg': {
            str(key): round(float(value), 2)
            for key, value in df.groupby('type_activite')['total_personnes'].mean().items()
        },
        'type_month_avg': {
            f"{type_activite}|{int(mois)}": round(float(value), 2)
            for (type_activite, mois), value in df.groupby(['type_activite', 'mois'])['total_personnes'].mean().items()
        },
        'type_month_day_avg': {
            f"{type_activite}|{int(mois)}|{int(jour)}": round(float(value), 2)
            for (type_activite, mois, jour), value in df.groupby(['type_activite', 'mois', 'jour_semaine'])['total_personnes'].mean().items()
        },
    }
    ,
    'capacity_stats': {
        'global_avg': round(float(df['capacite'].mean()), 2),
        'type_avg': {
            str(key): round(float(value), 2)
            for key, value in df.groupby('type_activite')['capacite'].mean().items()
        },
        'type_month_avg': {
            f"{type_activite}|{int(mois)}": round(float(value), 2)
            for (type_activite, mois), value in df.groupby(['type_activite', 'mois'])['capacite'].mean().items()
        },
        'type_month_day_avg': {
            f"{type_activite}|{int(mois)}|{int(jour)}": round(float(value), 2)
            for (type_activite, mois, jour), value in df.groupby(['type_activite', 'mois', 'jour_semaine'])['capacite'].mean().items()
        },
    }
}
with open(base_dir / 'model_meta.json', 'w', encoding='utf-8') as f:
    json.dump(meta, f, ensure_ascii=False, indent=2)
print("✅ model_meta.json sauvegardé")