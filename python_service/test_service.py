#!/usr/bin/env python3
"""
Service de test minimal pour la reconnaissance faciale
"""

from flask import Flask, request, jsonify
import base64
import io
from PIL import Image

app = Flask(__name__)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'ok',
        'message': 'Service de test minimal fonctionne',
        'version': '0.1.0'
    })

@app.route('/test-image', methods=['POST'])
def test_image():
    """Test basique de réception d'image"""
    try:
        data = request.get_json()
        if not data or 'image' not in data:
            return jsonify({'success': False, 'message': 'Aucune image reçue'}), 400

        # Décoder l'image base64
        image_data = data['image']
        if ',' in image_data:
            image_data = image_data.split(',')[1]

        # Convertir en bytes
        image_bytes = base64.b64decode(image_data)

        # Ouvrir avec PIL
        image = Image.open(io.BytesIO(image_bytes))

        return jsonify({
            'success': True,
            'message': f'Image reçue: {image.size[0]}x{image.size[1]} pixels',
            'size': image.size,
            'format': image.format,
            'mode': image.mode
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Erreur: {str(e)}'
        }), 500

if __name__ == '__main__':
    print("🚀 Démarrage du service de test minimal...")
    print("📍 URL: http://localhost:5000")
    print("🔗 Endpoints:")
    print("   GET  /health")
    print("   POST /test-image")
    app.run(host='0.0.0.0', port=5000, debug=True)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'ok',
        'message': 'Service de test minimal fonctionne',
        'version': '0.1.0'
    })

@app.route('/test-image', methods=['POST'])
def test_image():
    """Test basique de réception d'image"""
    try:
        data = request.get_json()
        if not data or 'image' not in data:
            return jsonify({'success': False, 'message': 'Aucune image reçue'}), 400

        # Décoder l'image base64
        image_data = data['image']
        if ',' in image_data:
            image_data = image_data.split(',')[1]

        # Convertir en bytes
        image_bytes = base64.b64decode(image_data)

        # Ouvrir avec PIL
        image = Image.open(io.BytesIO(image_bytes))

        return jsonify({
            'success': True,
            'message': f'Image reçue: {image.size[0]}x{image.size[1]} pixels',
            'size': image.size
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Erreur: {str(e)}'
        }), 500

if __name__ == '__main__':
    print("🚀 Démarrage du service de test minimal...")
    print("📍 URL: http://localhost:5000")
    print("🔗 Endpoints:")
    print("   GET  /health")
    print("   POST /test-image")
    app.run(host='0.0.0.0', port=5000, debug=True)