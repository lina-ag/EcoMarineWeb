#!/usr/bin/env python3
"""
Service de reconnaissance faciale avec face_recognition.
API Flask pour l'application EcoMarine.
"""

from __future__ import annotations

import base64
import binascii
import logging
import os
from io import BytesIO

import face_recognition
import numpy as np
from flask import Flask, jsonify, request
from PIL import Image, UnidentifiedImageError

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
app.config["JSON_AS_ASCII"] = False

FACE_DISTANCE_THRESHOLD = 0.6
DETECTION_MODEL = os.environ.get("FACE_DETECTION_MODEL", "hog")
ENCODING_JITTERS = int(os.environ.get("FACE_ENCODING_JITTERS", "1"))


def decode_base64_image(image_data: str) -> np.ndarray:
    """Convertit une image base64 en tableau numpy RGB."""
    if image_data.startswith("data:image"):
        parts = image_data.split(",", 1)
        image_data = parts[1] if len(parts) > 1 else image_data

    try:
        image_bytes = base64.b64decode(image_data)
        image = Image.open(BytesIO(image_bytes)).convert("RGB")
    except (binascii.Error, UnidentifiedImageError, OSError) as exc:
        raise ValueError("Image base64 invalide ou illisible.") from exc

    return np.array(image)


def detect_face_locations(image_array: np.ndarray) -> list[tuple[int, int, int, int]]:
    """Retourne la liste des visages detectes."""
    return face_recognition.face_locations(image_array, model=DETECTION_MODEL)


def extract_single_face_encoding(image_array: np.ndarray) -> tuple[list[float] | None, int]:
    """Extrait l'encodage d'un visage si l'image contient exactement une face."""
    face_locations = detect_face_locations(image_array)
    faces_count = len(face_locations)

    if faces_count != 1:
        return None, faces_count

    encodings = face_recognition.face_encodings(
        image_array,
        known_face_locations=face_locations,
        num_jitters=ENCODING_JITTERS,
    )

    if not encodings:
        return None, 0

    return encodings[0].tolist(), 1


@app.route("/health", methods=["GET"])
def health():
    """Verifie que le service est en ligne."""
    return jsonify(
        {
            "status": "ok",
            "service": "Face Recognition API",
            "version": "2.1.0",
            "detector": DETECTION_MODEL,
        }
    )


@app.route("/extract_encoding", methods=["POST"])
def extract_encoding():
    """Extrait l'encodage facial d'une image."""
    try:
        data = request.get_json(silent=True)
        if not data or "image" not in data:
            return (
                jsonify(
                    {
                        "success": False,
                        "message": "Aucune image recue",
                        "encoding": None,
                    }
                ),
                400,
            )

        image_array = decode_base64_image(data["image"])
        encoding, faces_count = extract_single_face_encoding(image_array)

        if faces_count == 0:
            return (
                jsonify(
                    {
                        "success": False,
                        "message": "Aucun visage detecte dans l'image",
                        "encoding": None,
                        "faces_count": 0,
                    }
                ),
                400,
            )

        if faces_count > 1:
            return (
                jsonify(
                    {
                        "success": False,
                        "message": (
                            f"Plusieurs visages detectes ({faces_count}). "
                            "Veuillez ne montrer que votre visage."
                        ),
                        "encoding": None,
                        "faces_count": faces_count,
                    }
                ),
                400,
            )

        if encoding is None:
            return (
                jsonify(
                    {
                        "success": False,
                        "message": "Impossible d'extraire un encodage facial valide.",
                        "encoding": None,
                        "faces_count": 0,
                    }
                ),
                400,
            )

        return (
            jsonify(
                {
                    "success": True,
                    "message": "Encodage facial extrait avec succes",
                    "encoding": encoding,
                    "faces_count": 1,
                }
            ),
            200,
        )

    except ValueError as exc:
        return jsonify({"success": False, "message": str(exc), "encoding": None}), 400
    except Exception as exc:
        logger.exception("Erreur extract_encoding")
        return (
            jsonify(
                {
                    "success": False,
                    "message": f"Erreur serveur: {exc}",
                    "encoding": None,
                }
            ),
            500,
        )


@app.route("/compare_faces", methods=["POST"])
def compare_faces():
    """Compare deux encodages faciaux."""
    try:
        data = request.get_json(silent=True)
        if not data or "encoding1" not in data or "encoding2" not in data:
            return jsonify({"success": False, "message": "Encodages manquants"}), 400

        enc1 = np.array(data["encoding1"], dtype=np.float64)
        enc2 = np.array(data["encoding2"], dtype=np.float64)

        if enc1.shape != enc2.shape:
            return (
                jsonify(
                    {
                        "success": False,
                        "message": "Les encodages ne sont pas compatibles.",
                    }
                ),
                400,
            )

        distance = float(face_recognition.face_distance([enc1], enc2)[0])
        match = distance < FACE_DISTANCE_THRESHOLD
        similarity_percentage = max(0.0, (1 - distance) * 100)

        return (
            jsonify(
                {
                    "success": True,
                    "match": bool(match),
                    "distance": distance,
                    "similarity_percentage": float(similarity_percentage),
                    "threshold": FACE_DISTANCE_THRESHOLD,
                }
            ),
            200,
        )

    except Exception as exc:
        logger.exception("Erreur compare_faces")
        return jsonify({"success": False, "message": f"Erreur serveur: {exc}"}), 500


@app.route("/verify_face", methods=["POST"])
def verify_face():
    """Verifie si une image contient exactement un visage valide."""
    try:
        data = request.get_json(silent=True)
        if not data or "image" not in data:
            return (
                jsonify(
                    {
                        "success": False,
                        "face_detected": False,
                        "message": "Aucune image recue",
                    }
                ),
                400,
            )

        image_array = decode_base64_image(data["image"])
        face_count = len(detect_face_locations(image_array))
        face_detected = face_count == 1

        return (
            jsonify(
                {
                    "success": True,
                    "face_detected": face_detected,
                    "face_count": face_count,
                    "message": (
                        "Visage detecte avec succes"
                        if face_detected
                        else f"{face_count} visage(s) detecte(s)"
                    ),
                }
            ),
            200,
        )

    except ValueError as exc:
        return (
            jsonify({"success": False, "face_detected": False, "message": str(exc)}),
            400,
        )
    except Exception as exc:
        logger.exception("Erreur verify_face")
        return (
            jsonify(
                {
                    "success": False,
                    "face_detected": False,
                    "message": f"Erreur serveur: {exc}",
                }
            ),
            500,
        )


if __name__ == "__main__":
    port = int(os.environ.get("FLASK_PORT", "5000"))
    logger.info("Serveur face_recognition demarre sur http://localhost:%s", port)
    app.run(host="0.0.0.0", port=port, debug=False, use_reloader=False)
