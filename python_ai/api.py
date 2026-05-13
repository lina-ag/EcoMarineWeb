from flask import Flask, request, jsonify
from ultralytics import YOLO
from PIL import Image
import io
import os
from pathlib import Path

app = Flask(__name__)

BASE_DIR = Path(__file__).resolve().parent

# Chemin vers votre modèle entraîné
MODEL_PATH = BASE_DIR / "runs" / "classify" / "afhq_classification_model" / "weights" / "best.pt"

# Variable globale pour stocker le modèle
model = None

def load_model():
    global model
    if MODEL_PATH.exists():
        print(f"Chargement du modèle: {MODEL_PATH}")
        model = YOLO(str(MODEL_PATH))
    else:
        print(f"ATTENTION: Le modèle {MODEL_PATH} n'existe pas encore.")
        print("L'API retournera des données de test (mock) en attendant la fin de l'entraînement.")

# Charger le modèle au démarrage
load_model()

@app.route('/predict', methods=['POST'])
def predict():
    data = request.get_json()
    
    if not data or 'image_base64' not in data:
        return jsonify({'error': 'Aucune image envoyée (image_base64 manquant)'}), 400
        
    # Decode base64 image
    import base64
    image_bytes = base64.b64decode(data['image_base64'])
    img = Image.open(io.BytesIO(image_bytes))

    # Si le modèle n'est pas encore entraîné, on simule une réponse
    if model is None:
        # On tente de recharger le modèle au cas où il a fini d'être entraîné entre temps
        load_model()
        if model is None:
            return jsonify({
                "espece": "Modèle en cours d'entraînement...",
                "nombre_individus": 1,
                "comportement": "En attente du modèle",
                "confiance_ia": "0%",
                "description": "Le modèle est toujours en cours d'entraînement. Revenez plus tard !"
            })

    # Si on a le modèle, on fait l'inférence
    results = model(img)
    
    # Pour la classification, on récupère la classe la plus probable
    result = results[0]
    names_dict = result.names
    probs = result.probs
    
    best_class_id = probs.top1
    best_class_name = names_dict[best_class_id]
    confidence = probs.top1conf.item() * 100
    
    # Mappage pour EcoMarine (adapter selon votre besoin)
    # AFHQ a les classes : cat, dog, wild
    espece_mappee = best_class_name
    comportement = "Observation"
    
    if best_class_name == "wild":
        espece_mappee = "Espèce sauvage (Loup/Renard/Tigre...)"
    elif best_class_name == "dog":
        espece_mappee = "Chien"
    elif best_class_name == "cat":
        espece_mappee = "Chat"

    response = {
        "espece": espece_mappee,
        "nombre_individus": 1, # La classification ne compte pas les individus, on met 1 par défaut
        "comportement": comportement,
        "confiance_ia": f"{confidence:.1f}%",
        "description": f"Classification: {espece_mappee} avec une certitude de {confidence:.1f}%."
    }

    return jsonify(response)

if __name__ == '__main__':
    print("Démarrage de l'API Python sur le port 5000...")
    app.run(host='127.0.0.1', port=5000, debug=True)
