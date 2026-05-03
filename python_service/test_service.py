#!/usr/bin/env python3
"""
Service de test minimal pour verifier la reception d'images.
"""

import base64
import binascii
import io

from flask import Flask, jsonify, request
from PIL import Image, UnidentifiedImageError

app = Flask(__name__)


@app.route("/health", methods=["GET"])
def health():
    return jsonify(
        {
            "status": "ok",
            "message": "Service de test minimal fonctionne",
            "version": "0.2.0",
        }
    )


@app.route("/test-image", methods=["POST"])
def test_image():
    """Test basique de reception d'image."""
    try:
        data = request.get_json(silent=True)
        if not data or "image" not in data:
            return jsonify({"success": False, "message": "Aucune image recue"}), 400

        image_data = data["image"]
        if image_data.startswith("data:image"):
            parts = image_data.split(",", 1)
            image_data = parts[1] if len(parts) > 1 else image_data

        image_bytes = base64.b64decode(image_data)
        image = Image.open(io.BytesIO(image_bytes))

        return jsonify(
            {
                "success": True,
                "message": f"Image recue: {image.size[0]}x{image.size[1]} pixels",
                "size": list(image.size),
                "format": image.format,
                "mode": image.mode,
            }
        )

    except (binascii.Error, UnidentifiedImageError, OSError):
        return (
            jsonify(
                {
                    "success": False,
                    "message": "Image base64 invalide ou illisible.",
                }
            ),
            400,
        )
    except Exception as exc:
        return jsonify({"success": False, "message": f"Erreur: {exc}"}), 500


if __name__ == "__main__":
    print("Demarrage du service de test minimal...")
    print("URL: http://localhost:5000")
    print("Endpoints:")
    print("  GET  /health")
    print("  POST /test-image")
    app.run(host="0.0.0.0", port=5000, debug=False, use_reloader=False)
