#!/usr/bin/env python3
"""
Service de reconnaissance faciale avec DeepFace
API Flask pour l'application EcoMarine
"""

import cv2
import numpy as np
import base64
import logging
from flask import Flask, request, jsonify
from PIL import Image
from io import BytesIO
from deepface import DeepFace
import os

# Configuration du logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False

# Seuil de similarité (distance cosine, plus petit = plus similaire)
FACE_DISTANCE_THRESHOLD = 0.4
MODEL_NAME = "VGG-Face"        # Modèle DeepFace (VGG-Face, Facenet, ArcFace...)
DETECTOR   = "opencv"          # Détecteur (opencv, ssd, mtcnn...)


def decode_base64_image(image_data: str) -> np.ndarray:
    """Convertit une image base64 en tableau numpy RGB"""
    if image_data.startswith('data:image'):
        image_data = image_data.split(',')[1]
    image_bytes = base64.b64decode(image_data)
    image = Image.open(BytesIO(image_bytes)).convert('RGB')
    return np.array(image)


def count_faces(image_array: np.ndarray) -> int:
    """Retourne le nombre de visages détectés dans l'image"""
    try:
        faces = DeepFace.extract_faces(
            img_path=image_array,
            detector_backend=DETECTOR,
            enforce_detection=True
        )
        return len(faces)
    except Exception:
        return 0


# ─────────────────────────────────────────────
# ROUTE : /health
# ─────────────────────────────────────────────
@app.route('/health', methods=['GET'])
def health():
    """Vérifier que le service est en ligne"""
    return jsonify({
        'status': 'ok',
        'service': 'Face Recognition API (DeepFace)',
        'version': '2.0.0'
    })


# ─────────────────────────────────────────────
# ROUTE : /extract_encoding
# ─────────────────────────────────────────────
@app.route('/extract_encoding', methods=['POST'])
def extract_encoding():
    """
    Extrait l'encodage facial d'une image.

    Requête JSON : { "image": "data:image/jpeg;base64,..." }
    Réponse JSON : { "success": true, "encoding": [...], "faces_count": 1 }
    """
    try:
        data = request.get_json()
        if not data or 'image' not in data:
            return jsonify({'success': False, 'message': 'Aucune image reçue', 'encoding': None}), 400

        image_array = decode_base64_image(data['image'])

        # Compter les visages
        faces_count = count_faces(image_array)

        if faces_count == 0:
            return jsonify({
                'success': False,
                'message': "Aucun visage détecté dans l'image",
                'encoding': None,
                'faces_count': 0
            }), 400

        if faces_count > 1:
            return jsonify({
                'success': False,
                'message': f'Plusieurs visages détectés ({faces_count}). Veuillez ne montrer que votre visage.',
                'encoding': None,
                'faces_count': faces_count
            }), 400

        # Extraire l'embedding (encodage)
        embedding_result = DeepFace.represent(
            img_path=image_array,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR,
            enforce_detection=False
        )

        encoding = embedding_result[0]['embedding']

        return jsonify({
            'success': True,
            'message': 'Encodage facial extrait avec succès',
            'encoding': encoding,
            'faces_count': 1
        }), 200

    except Exception as e:
        logger.error(f"Erreur extract_encoding: {e}")
        return jsonify({'success': False, 'message': f'Erreur serveur: {str(e)}', 'encoding': None}), 500


# ─────────────────────────────────────────────
# ROUTE : /compare_faces
# ─────────────────────────────────────────────
@app.route('/compare_faces', methods=['POST'])
def compare_faces():
    """
    Compare deux encodages faciaux (vecteurs numpy).

    Requête JSON : { "encoding1": [...], "encoding2": [...] }
    Réponse JSON : { "success": true, "match": true, "distance": 0.3, "similarity_percentage": 85.0 }
    """
    try:
        data = request.get_json()
        if not data or 'encoding1' not in data or 'encoding2' not in data:
            return jsonify({'success': False, 'message': 'Encodages manquants'}), 400

        enc1 = np.array(data['encoding1'])
        enc2 = np.array(data['encoding2'])

        # Distance cosine (utilisée par DeepFace par défaut)
        dot     = np.dot(enc1, enc2)
        norm    = np.linalg.norm(enc1) * np.linalg.norm(enc2)
        cosine_distance = 1 - (dot / norm if norm != 0 else 0)

        match = cosine_distance < FACE_DISTANCE_THRESHOLD
        similarity_percentage = max(0.0, (1 - cosine_distance) * 100)

        return jsonify({
            'success': True,
            'match': bool(match),
            'distance': float(cosine_distance),
            'similarity_percentage': float(similarity_percentage),
            'threshold': FACE_DISTANCE_THRESHOLD
        }), 200

    except Exception as e:
        logger.error(f"Erreur compare_faces: {e}")
        return jsonify({'success': False, 'message': f'Erreur serveur: {str(e)}'}), 500


# ─────────────────────────────────────────────
# ROUTE : /verify_face
# ─────────────────────────────────────────────
@app.route('/verify_face', methods=['POST'])
def verify_face():
    """
    Vérifie si une image contient exactement un visage valide.

    Requête JSON : { "image": "data:image/jpeg;base64,..." }
    Réponse JSON : { "success": true, "face_detected": true, "face_count": 1 }
    """
    try:
        data = request.get_json()
        if not data or 'image' not in data:
            return jsonify({'success': False, 'face_detected': False, 'message': 'Aucune image reçue'}), 400

        image_array = decode_base64_image(data['image'])
        face_count  = count_faces(image_array)
        face_detected = (face_count == 1)

        return jsonify({
            'success': True,
            'face_detected': face_detected,
            'face_count': face_count,
            'message': 'Visage détecté avec succès' if face_detected else f'{face_count} visage(s) détecté(s)'
        }), 200

    except Exception as e:
        logger.error(f"Erreur verify_face: {e}")
        return jsonify({'success': False, 'face_detected': False, 'message': f'Erreur serveur: {str(e)}'}), 500


if __name__ == '__main__':
    port = int(os.environ.get('FLASK_PORT', 5000))
    logger.info(f"🚀 Serveur DeepFace démarré sur http://localhost:{port}")
    app.run(host='0.0.0.0', port=port, debug=False)
