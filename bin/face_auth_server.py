import base64
import numpy as np
import cv2
import json
import mysql.connector
import face_recognition
from flask import Flask, request, jsonify
from flask_cors import CORS
import logging

# Suppress debug logs
log = logging.getLogger('werkzeug')
log.setLevel(logging.ERROR)

app = Flask(__name__)
CORS(app)

def get_db_connection():
    return mysql.connector.connect(host="127.0.0.1", user="root", password="", database="startupflow")

def decode_base64_image(base64_string):
    if "," in base64_string:
        base64_string = base64_string.split(',')[1]
    img_data = base64.b64decode(base64_string)
    nparr = np.frombuffer(img_data, np.uint8)
    return cv2.imdecode(nparr, cv2.IMREAD_COLOR)

@app.route('/api/register-face', methods=['POST'])
def register_face():
    data = request.json
    user_id = data.get('user_id')
    base64_img = data.get('image')

    if not user_id or not base64_img:
        return jsonify({"status": "error", "message": "Missing user_id or image"}), 400

    image = decode_base64_image(base64_img)
    if image is None:
        return jsonify({"status": "error", "message": "Invalid image format"}), 400

    # Convert to RGB (OpenCV uses BGR by default, face_recognition expects RGB)
    rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    face_locations = face_recognition.face_locations(rgb_image)
    
    if len(face_locations) == 0:
        return jsonify({"status": "error", "message": "No face detected. Please ensure your face is clearly visible in the frame."})
    if len(face_locations) > 1:
        return jsonify({"status": "error", "message": "Multiple faces detected. Please ensure only ONE face is visible."})

    encodings = face_recognition.face_encodings(rgb_image, face_locations)
    if len(encodings) == 0:
        return jsonify({"status": "error", "message": "Could not extract facial geometries. Try better lighting."})

    # Encode numpy array to standard JSON List
    face_encoding_json = json.dumps(encodings[0].tolist())

    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute("UPDATE users SET face_encoding = %s WHERE id = %s", (face_encoding_json, user_id))
        conn.commit()
    except Exception as e:
        return jsonify({"status": "error", "message": "Database write error: " + str(e)})
    finally:
        cursor.close()
        conn.close()

    print(f"[REGISTER] Successfully registered face for User ID: {user_id}")
    return jsonify({"status": "success", "message": "Face registered successfully"})

@app.route('/api/login-face', methods=['POST'])
def login_face():
    data = request.json
    base64_img = data.get('image')

    if not base64_img:
        return jsonify({"status": "error", "message": "No image provided"}), 400

    image = decode_base64_image(base64_img)
    if image is None:
        return jsonify({"status": "error", "message": "Invalid image format"}), 400

    rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    face_locations = face_recognition.face_locations(rgb_image)

    if len(face_locations) == 0:
        return jsonify({"status": "error", "message": "No face detected in the frame."})
    
    encodings = face_recognition.face_encodings(rgb_image, face_locations)
    if len(encodings) == 0:
        return jsonify({"status": "error", "message": "Could not extract face geometries."})

    test_encoding = encodings[0]

    # Fetch all stored face encodings from MySQL
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT id, face_encoding FROM users WHERE face_encoding IS NOT NULL")
    users = cursor.fetchall()
    cursor.close()
    conn.close()

    if not users:
        return jsonify({"status": "error", "message": "No faces are enrolled in the system."})

    best_match_id = None
    min_distance = 1.0

    # Execute matching algorithm
    for user in users:
        try:
            known_encoding = np.array(json.loads(user['face_encoding']))
            # Compute geometric distance between frames (The smaller the distance, the strictly closer the match)
            distance = face_recognition.face_distance([known_encoding], test_encoding)[0]
            
            # Use strict distance threshold (default is 0.60, 0.55 is tighter to prevent spoofing)
            if distance < min_distance and distance < 0.55: 
                min_distance = distance
                best_match_id = user['id']
        except Exception:
            continue

    if best_match_id:
        print(f"[MATCH SUCCESS] Authenticated User ID: {best_match_id} | Confidence Distance: {min_distance:.3f}")
        return jsonify({"status": "success", "user_id": best_match_id, "confidence": round(1 - min_distance, 2)})
    else:
        print(f"[MATCH FAILED] Face not recognized. Best distance was {min_distance:.3f} > 0.55 limit.")
        return jsonify({"status": "error", "message": "This face is not recognized. Please try again or use email/password."})

if __name__ == "__main__":
    print("====================================")
    print("🚀 Face Authentication Microservice ")
    print("🟢 Listening dynamically on Port 5001")
    print("====================================")
    app.run(host="127.0.0.1", port=5001, debug=False)
