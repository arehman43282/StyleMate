import sys
import os
import json
import numpy as np
import warnings

# --- AGGRESSIVE SUPPRESSION ---
# 1. Suppress all Python warnings
warnings.filterwarnings("ignore")
# 2. Suppress TensorFlow logs (must be done BEFORE importing TF)
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'

# 3. Redirect BOTH stdout and stderr during heavy imports
_stdout = sys.stdout
_stderr = sys.stderr
sys.stdout = open(os.devnull, 'w')
sys.stderr = open(os.devnull, 'w')

import cv2
import cvlib as cv

# Restore stdout and stderr
sys.stdout = _stdout
sys.stderr = _stderr

# Optional PIL import to prevent crash
try:
    from PIL import Image
    PIL_AVAILABLE = True
except ImportError:
    PIL_AVAILABLE = False

def analyze_skin_tone(image_path):
    try:
        if not os.path.exists(image_path):
            return {"success": False, "message": "File not found at " + image_path}

        # Load image with OpenCV for face and gender detection
        img_cv = cv2.imread(image_path)
        if img_cv is None:
            return {"success": False, "message": "Failed to load image with OpenCV"}

        # --- DEEP LEARNING GENDER DETECTION ---
        gender = None
        gender_conf = 0
        try:
            # Silence internal library prints during detection
            _inner_stdout = sys.stdout
            _inner_stderr = sys.stderr
            sys.stdout = open(os.devnull, 'w')
            sys.stderr = open(os.devnull, 'w')
            
            # Detect faces
            faces, confidences = cv.detect_face(img_cv)
            
            if faces:
                # Take the largest face
                largest_face = max(faces, key=lambda f: (f[2]-f[0]) * (f[3]-f[1]))
                x1, y1, x2, y2 = largest_face
                
                # Crop face for gender detection with a bit of padding
                h, w = img_cv.shape[:2]
                padding = 20
                nx1 = max(0, x1 - padding)
                ny1 = max(0, y1 - padding)
                nx2 = min(w, x2 + padding)
                ny2 = min(h, y2 + padding)
                face_crop = img_cv[ny1:ny2, nx1:nx2]
                
                if face_crop.size > 0:
                    # Detect gender
                    labels, confs = cv.detect_gender(face_crop)
                    if labels:
                        idx = np.argmax(confs)
                        detected = labels[idx]
                        gender_conf = float(confs[idx])
                        
                        # Strict confidence threshold
                        if gender_conf > 0.6:
                            # cvlib returns "male" or "female"
                            gender = "Boy" if detected == "male" else "Girl"
            
            # Restore
            sys.stdout = _inner_stdout
            sys.stderr = _inner_stderr
            
            if gender is None:
                if not faces:
                    raise Exception("No face detected. Please ensure your face is clearly visible.")
                else:
                    raise Exception(f"Gender detection uncertain (confidence: {gender_conf:.2f}). Please try a clearer photo.")

        except Exception as e:
            # Restore on error
            if '_inner_stdout' in locals(): sys.stdout = _inner_stdout
            if '_inner_stderr' in locals(): sys.stderr = _inner_stderr
            # Re-raise exception to be caught by main block
            raise e

        # --- SKIN TONE ANALYSIS ---
        if PIL_AVAILABLE:
            img_pil = Image.open(image_path).convert('RGB')
            width, height = img_pil.size
            
            if 'faces' in locals() and faces:
                x1, y1, x2, y2 = largest_face
                img_skin = img_pil.crop((max(0, x1), max(0, y1), min(width, x2), min(height, y2)))
            else:
                left = width * 0.35
                top = height * 0.35
                right = width * 0.65
                bottom = height * 0.65
                img_skin = img_pil.crop((left, top, right, bottom))
            
            img_skin = img_skin.resize((50, 50))
            pixels = np.array(img_skin)
            avg_color = np.mean(pixels, axis=(0, 1))
            r, g, b = avg_color
        else:
            avg_color = np.mean(img_cv, axis=(0, 1))
            b, g, r = avg_color
        
        # UNDERTONE DETECTION
        diff_rg = r - g
        diff_rb = r - b
        
        if diff_rg > 15 and diff_rb > 25:
            undertone = "Warm"
        elif b > g or (abs(r-b) < 10 and b > 100):
            undertone = "Cool"
        else:
            undertone = "Neutral"

        # BRIGHTNESS/DEPTH
        brightness = (0.299 * r) + (0.587 * g) + (0.114 * b)
        if brightness > 200: tone = "Fair"
        elif brightness > 170: tone = "Light-Medium"
        elif brightness > 140: tone = "Medium"
        elif brightness > 100: tone = "Tan"
        else: tone = "Deep"

        # SEASON MAPPING
        if undertone == "Warm":
            season = "Spring" if brightness > 150 else "Autumn"
        elif undertone == "Cool":
            season = "Summer" if brightness > 150 else "Winter"
        else:
            season = "Spring" if brightness > 150 else "Autumn"

        # Palettes and Tips
        palettes = {
            "Spring": {
                "colors": [{"name": "Peach", "hex": "#FFCC99"}, {"name": "Golden Yellow", "hex": "#FFDF00"}, {"name": "Warm Aqua", "hex": "#00FFFF"}, {"name": "Coral", "hex": "#FF7F50"}],
                "db_queries": ["peach", "gold", "yellow", "aqua", "coral", "cream"]
            },
            "Autumn": {
                "colors": [{"name": "Burnt Orange", "hex": "#CC5500"}, {"name": "Olive Green", "hex": "#808000"}, {"name": "Mustard", "hex": "#FFDB58"}, {"name": "Terracotta", "hex": "#E2725B"}],
                "db_queries": ["orange", "olive", "mustard", "brown", "rust", "maroon"]
            },
            "Summer": {
                "colors": [{"name": "Sky Blue", "hex": "#87CEEB"}, {"name": "Soft Pink", "hex": "#FFB6C1"}, {"name": "Lavender", "hex": "#E6E6FA"}, {"name": "Mint Green", "hex": "#98FF98"}],
                "db_queries": ["blue", "pink", "purple", "mint", "silver", "white"]
            },
            "Winter": {
                "colors": [{"name": "Royal Blue", "hex": "#002366"}, {"name": "Emerald Green", "hex": "#50C878"}, {"name": "Magenta", "hex": "#FF00FF"}, {"name": "Pure Black", "hex": "#000000"}],
                "db_queries": ["navy", "emerald", "magenta", "black", "silver", "white"]
            }
        }

        style_tips = {
            "Spring": {
                "Boy": "Your warm glow is perfect for bright polos and cream trousers. Try a golden yellow shirt for a fresh look.",
                "Girl": "Bright, clear colors like peach and coral are stunning on you. A warm aqua dress will make your skin radiate."
            },
            "Autumn": {
                "Boy": "Rich earthy tones like olive and rust are your power colors. Pair an orange sweater with brown chinos.",
                "Girl": "Terracotta and mustard yellows highlight your warm depth perfectly. Earthy maroons are great for evening wear."
            },
            "Summer": {
                "Boy": "Cool, soft colors like sky blue and mint green look effortless. Try silver-grey for formal outfits.",
                "Girl": "Lavender and soft pinks complement your cool undertones beautifully. Avoid harsh oranges for a softer look."
            },
            "Winter": {
                "Boy": "You have high contrast! Bold navy and emerald green look sharp. Pure black is your best formal color.",
                "Girl": "Deep jewel tones like magenta and emerald are stunning. Bold contrasts like black and white look very chic."
            }
        }

        return {
            "success": True,
            "r": int(r), "g": int(g), "b": int(b),
            "tone": tone,
            "undertone": undertone,
            "season": season,
            "gender": gender,
            "recommended_colors": palettes[season]["colors"],
            "db_colors": palettes[season]["db_queries"],
            "style_tip": style_tips[season][gender]
        }

    except Exception as e:
        return {"success": False, "message": str(e)}

if __name__ == "__main__":
    if len(sys.argv) > 1:
        img_path = sys.argv[1]
        result = analyze_skin_tone(img_path)
        print(json.dumps(result))
    else:
        print(json.dumps({"success": False, "message": "No image path provided"}))
