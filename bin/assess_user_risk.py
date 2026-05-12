#!/usr/bin/env python3
import sys
import json
import os
import joblib
import warnings

# Suppress warnings
warnings.filterwarnings('ignore')

def main():
    try:
        # Read JSON from standard input
        input_data = sys.stdin.read()
        reclamations_text = json.loads(input_data)
        
        if not isinstance(reclamations_text, list):
            print(json.dumps({"error": "Input must be a list of strings"}))
            sys.exit(1)
            
        model_path = os.path.join(os.path.dirname(__file__), 'risk_model.pkl')
        if not os.path.exists(model_path):
            print(json.dumps({"error": "Missing risk_model.pkl. Run train_risk_model.py first."}))
            sys.exit(1)
            
        model = joblib.load(model_path)
        
        weights = {
            "MINOR": 5,
            "NORMAL": 10,
            "SERIOUS": 25,
            "CRITICAL": 50
        }
        
        total_score = 0
        
        if len(reclamations_text) > 0:
            predictions = model.predict(reclamations_text)
            for p in predictions:
                total_score += weights.get(p, 0)
                
        # Classification Engine Rules
        if total_score > 80:
            risk_level = "DANGEROUS"
        elif total_score > 40:
            risk_level = "SUSPICIOUS"
        else:
            risk_level = "NORMAL"
            
        result = {
            "score": total_score,
            "level": risk_level,
            "status": "success"
        }
        
        print(json.dumps(result))
        sys.exit(0)
        
    except Exception as e:
        print(json.dumps({"error": str(e), "status": "failed"}))
        sys.exit(1)

if __name__ == "__main__":
    main()
