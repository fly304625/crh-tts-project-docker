from transformers import VitsModel, AutoProcessor
import os

def download_model():
    # Створюємо директорію для моделі
    model_path = "models/mms-tts-crh"
    os.makedirs(model_path, exist_ok=True)
    
    # Завантажуємо модель та процесор
    model = VitsModel.from_pretrained("facebook/mms-tts-crh")
    processor = AutoProcessor.from_pretrained("facebook/mms-tts-crh")
    
    # Зберігаємо локально
    model.save_pretrained(model_path)
    processor.save_pretrained(model_path)
    
    print(f"Model and processor saved to {model_path}")

if __name__ == "__main__":
    download_model()
