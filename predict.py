import json
import pickle
import sys
from pathlib import Path

try:
    import pandas as pd
except Exception:
    pd = None


# python predict.py <mois> <jour_semaine> <capacite> <type_activite>
if len(sys.argv) != 5:
    print(json.dumps({"error": "Arguments manquants"}))
    sys.exit(1)

mois = int(sys.argv[1])
jour_semaine = int(sys.argv[2])
capacite = int(sys.argv[3])
type_activite = sys.argv[4].strip().lower()

base_dir = Path(__file__).resolve().parent
meta_path = base_dir / 'model_meta.json'


def load_meta():
    if not meta_path.exists():
        return {}

    try:
        with open(meta_path, 'r', encoding='utf-8') as meta_file:
            return json.load(meta_file)
    except Exception:
        return {}


meta = load_meta()
features = meta.get('features', ['mois', 'jour_semaine', 'capacite', 'type_activite_enc'])


def resolve_expected_demand(type_value: str, month_value: int, day_value: int) -> float:
    demand_stats = meta.get('demand_stats', {}) or {}
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
    capacity_stats = meta.get('capacity_stats', {}) or {}
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
        return 1.0


model = None
label_encoder = None
model_error = None

try:
    with open(base_dir / 'model.pkl', 'rb') as model_file:
        model = pickle.load(model_file)
except Exception as exc:
    model_error = str(exc)

try:
    with open(base_dir / 'label_encoder.pkl', 'rb') as encoder_file:
        label_encoder = pickle.load(encoder_file)
except Exception:
    label_encoder = None

classes = list(getattr(label_encoder, 'classes_', [])) if label_encoder is not None else []
if type_activite in classes:
    type_enc = label_encoder.transform([type_activite])[0]
else:
    type_enc = 0

model_risk = 0.0

if model is not None and pd is not None:
    try:
        sample = pd.DataFrame([[mois, jour_semaine, capacite, type_enc]], columns=features)
        probabilities = model.predict_proba(sample)[0]
        if len(probabilities) > 1:
            model_risk = round(float(probabilities[1]) * 100.0, 1)
    except Exception as exc:
        model_error = str(exc)

expected_capacity = resolve_expected_capacity(type_activite, mois, jour_semaine)
expected_demand = resolve_expected_demand(type_activite, mois, jour_semaine)
capacity = max(capacite, 1)
target_capacity = max(1.0, expected_capacity)
deviation_pct = abs(capacity - target_capacity) / max(capacity, target_capacity) * 100.0
risque_pct = round(min(100.0, (deviation_pct * 0.9) + (model_risk * 0.1)), 1)

cap_optimale = max(1, int(round(target_capacity)))
optimal_deviation_pct = abs(cap_optimale - target_capacity) / max(cap_optimale, target_capacity) * 100.0 if target_capacity else 0.0
risque_optimal = round(min(100.0, (optimal_deviation_pct * 0.9) + (model_risk * 0.1)), 1)

if risque_pct >= 70:
    niveau = "eleve"
    message = f"Risque eleve ({risque_pct}%) que cette activite parte sous-remplie. Pensez a reduire la capacite ou changer la date."
    couleur = "danger"
elif risque_pct >= 40:
    niveau = "moyen"
    message = f"Risque modere ({risque_pct}%). Cette activite pourrait ne pas atteindre la moitie de sa capacite."
    couleur = "warning"
else:
    niveau = "faible"
    message = f"Faible risque ({risque_pct}%). Cette activite devrait bien se remplir."
    couleur = "success"

result = {
    "risque_pct": risque_pct,
    "niveau": niveau,
    "message": message,
    "couleur": couleur,
    "capacite_optimale": cap_optimale,
    "risque_optimale": risque_optimal,
    "capacite_historique_estimee": round(target_capacity, 1),
    "demande_historique_estimee": round(expected_demand, 1),
    "ecart_pourcent": round(deviation_pct, 1),
    "modele_disponible": model is not None and pd is not None,
}

if model_error:
    result["modele_info"] = model_error

print(json.dumps(result))
