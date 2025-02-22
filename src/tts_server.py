import os
import logging
from datetime import datetime
from pathlib import Path
from dotenv import load_dotenv
from flask import Flask, request, jsonify
from transformers import VitsModel, AutoProcessor
import torch
import soundfile as sf
import hashlib

# Завантаження змінних середовища
load_dotenv()

# Налаштування логування
log_dir = Path(os.getenv('LOG_DIR', 'logs'))
log_dir.mkdir(exist_ok=True)
log_file = log_dir / f"tts_server_{datetime.now().strftime('%Y%m%d')}.log"
logging.basicConfig(
    filename=log_file,
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

app = Flask(__name__)

class TTSService:
    def __init__(self):
        # Перевіряємо наявність локальної моделі
        local_model_path = Path("models/mms-tts-crh")
        if local_model_path.exists():
            self.model_name = str(local_model_path)
            logger.info(f"Using local model from: {self.model_name}")
        else:
            self.model_name = os.getenv('MODEL_NAME', 'facebook/mms-tts-crh')
            logger.info(f"Using model from HuggingFace: {self.model_name}")
        
        self.output_dir = Path(os.getenv('OUTPUT_DIR', 'output'))
        self.output_dir.mkdir(exist_ok=True)
        
        try:
            self.model = VitsModel.from_pretrained(self.model_name)
            self.processor = AutoProcessor.from_pretrained(self.model_name)
            self.device = "cuda" if torch.cuda.is_available() else "cpu"
            logger.info(f"Using device: {self.device}")
            self.model.to(self.device)
        except Exception as e:
            logger.error(f"Error initializing model: {str(e)}")
            raise

    def generate_speech(self, text, language="crh"):
        try:
            logger.info(f"Generating speech for text: {text[:50]}...")
            
            inputs = self.processor(text=text, return_tensors="pt", language=language)
            with torch.no_grad():
                outputs = self.model(**inputs.to(self.device))
            
            waveform = outputs.waveform[0].cpu().numpy()
            
            # Create filename based on text hash
            filename = hashlib.md5(text.encode()).hexdigest() + '.wav'
            output_path = self.output_dir / filename
            
            # Save audio file
            sf.write(str(output_path), waveform, samplerate=16000)
            logger.info(f"Successfully generated audio file: {filename}")
            
            return filename
        except Exception as e:
            logger.error(f"Error generating speech: {str(e)}")
            raise

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({'status': 'healthy'}), 200

@app.route('/generate', methods=['POST'])
def generate():
    try:
        text = request.json.get('text', '')
        if not text:
            logger.warning("Received empty text request")
            return jsonify({'error': 'No text provided'}), 400
        
        language = request.json.get('language', 'crh')
        
        tts_service = TTSService()
        filename = tts_service.generate_speech(text, language)
        
        return jsonify({
            'success': True,
            'filename': filename
        })
    except Exception as e:
        logger.error(f"Error processing request: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=int(os.getenv('PORT', 5000)))
