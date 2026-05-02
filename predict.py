import sys
import pickle
import json
from pathlib import Path
import pandas as pd

# ─── ARGUMENTS depuis Symfony ────────────────────────────────────────────────
# python predict.py <mois> <jour_semaine> <capacite> <type_activite>
if len(sys.argv) != 5:
    print(json.dumps({"error": "Arguments manquants"}))
    sys.exit(1)

mois          = int(sys.argv[1])
jour_semaine  = int(sys.argv[2])
capacite      = int(sys.argv[3])
type_activite = sys.argv[4].strip().lower()

base_dir = Path(__file__).resolve().parent

# ─── CHARGEMENT DU MODÈLE ────────────────────────────────────────────────────
with open(base_dir / 'model.pkl', 'rb') as f:
    model = pickle.load(f)

with open(base_dir / 'label_encoder.pkl', 'rb') as f:
    le = pickle.load(f)

# Load features from model metadata if available
meta_path = base_dir / 'model_meta.json'
if meta_path.exists():
    try:
        with open(meta_path, 'r', encoding='utf-8') as mf:
            meta = json.load(mf)
            features = meta.get('features', ['mois', 'jour_semaine', 'capacite', 'type_activite_enc'])
    except Exception:
        features = ['mois', 'jour_semaine', 'capacite', 'type_activite_enc']
else:
    features = ['mois', 'jour_semaine', 'capacite', 'type_activite_enc']

def resolve_expected_demand(type_value: str, month_value: int, day_value: int) -> float:
    """Estimate expected attendance from stored historical averages."""
    demand_stats = {}
    if meta_path.exists():
        try:
            with open(meta_path, 'r', encoding='utf-8') as mf:
                demand_stats = json.load(mf).get('demand_stats', {}) or {}
        except Exception:
            demand_stats = {}

    type_key = (type_value or '').strip().lower()
    candidates = [
        ('type_month_day_avg', f'{type_key}|{month_value}|{day_value}'),
        ('type_month_avg', f'{type_key}|{month_value}'),
        ('type_avg', type_key),
    ]

    for bucket_name, bucket_key in candidates:
        bucket = demand_stats.get(bucket_name, {})
        if isinstance(bucket, dict) and bucket_key in bucket:
            try:
                return float(bucket[bucket_key])
            except (TypeError, ValueError):
                pass

    try:
        return float(demand_stats.get('global_avg', 0))
    except (TypeError, ValueError):
        return 0.0

def resolve_expected_capacity(type_value: str, month_value: int, day_value: int) -> float:
    """Estimate a realistic capacity from historical activity capacities."""
    capacity_stats = {}
    if meta_path.exists():
        try:
            with open(meta_path, 'r', encoding='utf-8') as mf:
                capacity_stats = json.load(mf).get('capacity_stats', {}) or {}
        except Exception:
            capacity_stats = {}

    type_key = (type_value or '').strip().lower()
    candidates = [
        ('type_month_day_avg', f'{type_key}|{month_value}|{day_value}'),
        ('type_month_avg', f'{type_key}|{month_value}'),
        ('type_avg', type_key),
    ]

    for bucket_name, bucket_key in candidates:
        bucket = capacity_stats.get(bucket_name, {})
        if isinstance(bucket, dict) and bucket_key in bucket:
            try:
                value = float(bucket[bucket_key])
                if value > 0:
                    return value
            except (TypeError, ValueError):
                pass

    try:
        value = float(capacity_stats.get('global_avg', 0))
        return value if value > 0 else 1.0
    except (TypeError, ValueError):
        return 0.0

# ─── ENCODAGE DU TYPE ────────────────────────────────────────────────────────
classes = list(le.classes_)
if type_activite in classes:
    type_enc = le.transform([type_activite])[0]
else:
    # Type inconnu → on prend la classe la plus fréquente (index 0)
    type_enc = 0

# ─── PRÉDICTION ──────────────────────────────────────────────────────────────
X = pd.DataFrame([[mois, jour_semaine, capacite, type_enc]], columns=features)
prediction    = model.predict(X)[0]
probabilites  = model.predict_proba(X)[0]

# Variable risk driven by historical capacity, with the tree probability as a weak signal.
expected_capacity = resolve_expected_capacity(type_activite, mois, jour_semaine)
capacity = max(capacite, 1)
target_capacity = max(1.0, expected_capacity)
deviation_pct = abs(capacity - target_capacity) / max(capacity, target_capacity) * 100.0
model_risk = round(float(probabilites[1]) * 100.0, 1) if len(probabilites) > 1 else 0.0
risque_pct = round(min(100.0, (deviation_pct * 0.9) + (model_risk * 0.1)), 1)

cap_optimale = max(1, int(round(target_capacity)))

optimal_deviation_pct = abs(cap_optimale - target_capacity) / max(cap_optimale, target_capacity) * 100.0 if target_capacity else 0.0
risque_optimal = round(min(100.0, (optimal_deviation_pct * 0.9) + (model_risk * 0.1)), 1)

# ─── RÉSULTAT JSON ───────────────────────────────────────────────────────────
if risque_pct >= 70:
    niveau  = "eleve"
    message = f"⚠️ Risque élevé ({risque_pct}%) que cette activité parte sous-remplie. Pensez à réduire la capacité ou changer la date."
    couleur = "danger"
elif risque_pct >= 40:
    niveau  = "moyen"
    message = f"🟡 Risque modéré ({risque_pct}%). Cette activité pourrait ne pas atteindre la moitié de sa capacité."
    couleur = "warning"
else:
    niveau  = "faible"
    message = f"✅ Faible risque ({risque_pct}%). Cette activité devrait bien se remplir !"
    couleur = "success"

print(json.dumps({
    "risque_pct": risque_pct,
    "niveau":     niveau,
    "message":    message,
    "couleur":    couleur
    ,"capacite_optimale": cap_optimale,
    "risque_optimale": risque_optimal,
    "capacite_historique_estimee": round(target_capacity, 1),
    "ecart_pourcent": round(deviation_pct, 1)
}))