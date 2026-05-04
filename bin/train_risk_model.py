#!/usr/bin/env python3
import os
import joblib
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import make_pipeline

X_train = [
    "service is a bit slow", "late delivery", "small delay in response", 
    "typo in the document", "minor issue with the interface", "page loaded slowly",
    "forgot to attach the file",
    
    "bad customer service", "unhelpful staff", "the product was damaged",
    "terrible experience", "they ignored my emails", "complaint about mentor", 
    "unprofessional behavior",
    
    "this person is abusive", "fraudulent activity detected", "he insulted me",
    "fake profile", "used my data without permission", "harassment",
    "stole my idea", "plagiarism detected",
    
    "they stole my money", "illegal activities", "scam artist", "financial theft",
    "child abuse", "death threat", "selling illegal drugs", "ponzi scheme",
    "dangerous criminal activity" , "bias detected"
]

y_train = [
    "MINOR", "MINOR", "MINOR", "MINOR", "MINOR", "MINOR", "MINOR",
    "NORMAL", "NORMAL", "NORMAL", "NORMAL", "NORMAL", "NORMAL", "NORMAL",
    "SERIOUS", "SERIOUS", "SERIOUS", "SERIOUS", "SERIOUS", "SERIOUS", "SERIOUS", "SERIOUS",
    "CRITICAL", "CRITICAL", "CRITICAL", "CRITICAL", "CRITICAL", "CRITICAL", "CRITICAL", "CRITICAL", "CRITICAL", "CRITICAL"
]

print("Training Machine Learning Model for Reclamation Severity...")

model = make_pipeline(TfidfVectorizer(lowercase=True, stop_words='english'), MultinomialNB())

model.fit(X_train, y_train)

model_path = os.path.join(os.path.dirname(__file__), 'risk_model.pkl')
joblib.dump(model, model_path)

print(f"Model successfully trained and saved to {model_path}")
