import os
from ultralytics import YOLO

def main():
    print("=== Démarrage de l'entraînement de Classification Animale ===")
    
    # 1. Charger un modèle de CLASSIFICATION pré-entraîné (notez le '-cls')
    model = YOLO("yolov8n-cls.pt")
    
    # 2. Chemin vers le dataset que vous venez de télécharger
    dataset_path = r"C:\Users\medso\Downloads\archive\afhq"
    
    if not os.path.exists(dataset_path):
        print(f"Erreur: Le dossier {dataset_path} est introuvable.")
        return

    # 3. Lancer l'entraînement
    print(f"Entraînement en cours sur le dataset: {dataset_path}...")
    results = model.train(
        data=dataset_path,
        epochs=1,        # Set to 1 epoch for testing because CPU training is very slow!
        imgsz=128,       # Reduced size for faster testing
        batch=16,        
        name="afhq_classification_model",
        device="cpu"     
    )
    
    print("=== Entraînement terminé ! ===")
    print("Le meilleur modèle a été sauvegardé dans: runs/classify/afhq_classification_model/weights/best.pt")

if __name__ == "__main__":
    main()
