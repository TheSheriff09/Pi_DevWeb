#!/usr/bin/env python3
import os
import joblib
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import make_pipeline

# Model Training Data (Synthetic)
# Used to classify reclamations into 4 severity levels
X_train = [
    # Minor (delay, small issue)
    "service is a bit slow", "late delivery", "small delay in response", 
    "typo in the document", "minor issue with the interface", "page loaded slowly",
    "forgot to attach the file",
    
    # Normal (bad service, complaint)
    "bad customer service", "unhelpful staff", "the product was damaged",
    "terrible experience", "they ignored my emails", "complaint about mentor", 
    "unprofessional behavior",
    
    # Serious (fraud, abuse)
    "this person is abusive", "fraudulent activity detected", "he insulted me",
    "fake profile", "used my data without permission", "harassment",
    "stole my idea", "plagiarism detected",
    
    # Critical (scam, illegal)
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

# Create a TF-IDF vectorizer + Naive Bayes Classifier pipeline
model = make_pipeline(TfidfVectorizer(lowercase=True, stop_words='english'), MultinomialNB())

# Train the model
model.fit(X_train, y_train)

# Save the model
model_path = os.path.join(os.path.dirname(__file__), 'risk_model.pkl')
joblib.dump(model, model_path)

print(f"Model successfully trained and saved to {model_path}")
