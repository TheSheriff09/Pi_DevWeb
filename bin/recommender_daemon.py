import os
import time
import json
import logging
from datetime import datetime
import pandas as pd
import mysql.connector
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler

# Set up logging for terminal output
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# Ensure the var directory exists for the json dump
VAR_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'var')
os.makedirs(VAR_DIR, exist_ok=True)
JSON_OUTPUT_PATH = os.path.join(VAR_DIR, 'recommended_post.json')

def get_db_connection():
    try:
        return mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database="startupflow"
        )
    except Exception as e:
        logging.error(f"Failed to connect to database: {e}")
        return None

def fetch_data(conn):
    try:
        # Fetch Posts
        posts_df = pd.read_sql("SELECT id, title, created_at FROM forum_posts", conn)
        
        # Fetch Comments count
        comments_df = pd.read_sql("SELECT post_id, COUNT(*) as comment_count FROM comments GROUP BY post_id", conn)
        
        # Fetch Interaction counts (Likes, Loves, etc. all count as positive engagement)
        likes_df = pd.read_sql("SELECT post_id, COUNT(*) as like_count FROM interactions GROUP BY post_id", conn)
        
        # We don't have a reliable 'view' table in current DB schema, so we assign views = 0
        views_df = pd.DataFrame(columns=['post_id', 'view_count'])

        # Merge dataframes
        df = posts_df.copy()
        df = df.merge(comments_df, left_on='id', right_on='post_id', how='left').drop(columns=['post_id'])
        df = df.merge(likes_df, left_on='id', right_on='post_id', how='left').drop(columns=['post_id'])
        df = df.merge(views_df, left_on='id', right_on='post_id', how='left').drop(columns=['post_id'])
        
        # Fill missing values with 0
        df['comment_count'] = df['comment_count'].fillna(0)
        df['like_count'] = df['like_count'].fillna(0)
        df['view_count'] = df['view_count'].fillna(0)
        
        return df
    except Exception as e:
        logging.error(f"Error fetching data: {e}")
        return pd.DataFrame()

def run_ml_pipeline():
    logging.info("Starting ML recommendation pipeline iteration...")
    conn = get_db_connection()
    if not conn:
        return

    df = fetch_data(conn)
    conn.close()

    if df.empty or len(df) < 2:
        logging.warning("Not enough forum posts to train a recommendation model.")
        return

    # 1. Feature Engineering
    logging.info(f"Computing features for {len(df)} posts...")
    
    # Calculate age in hours
    now = datetime.now()
    # Convert created_at to proper datetime objects, handling timezone unawareness by normalizing
    df['created_at'] = pd.to_datetime(df['created_at'])
    df['age_hours'] = (now - df['created_at']).dt.total_seconds() / 3600.0
    
    # Avoid div by zero
    df['age_hours'] = df['age_hours'].apply(lambda x: max(x, 0.1))

    # Base Engagement Score formula
    df['engagement_score'] = (df['like_count'] * 2.0) + df['comment_count'] + (df['view_count'] * 0.5)

    # Completely Bypass Time Decay for Testing (Strict All-Time Leaderboard)
    # The user desires "5 likes always beats 3 likes", regardless of whether the post is 2 months old or 2 hours old. 
    df['decayed_score'] = df['engagement_score'] 

    # 2. Heuristic Labelling (Top 10% highest scores are '1', rest '0')
    threshold = df['decayed_score'].quantile(0.9)
    # If all scores are 0, fallback to top 1
    if threshold == 0:
         df['is_trending'] = 0
         top_idx = df['engagement_score'].idxmax()
         df.loc[top_idx, 'is_trending'] = 1
    else:
        df['is_trending'] = (df['decayed_score'] >= threshold).astype(int)

    logging.info(f"Target logic generated: {df['is_trending'].sum()} posts marked as historical trending.")

    # 3. Model Training
    features = ['like_count', 'comment_count', 'view_count', 'age_hours']
    X = df[features]
    y = df['is_trending']

    # Scale features for Logistic Regression convergence
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    # Use Logistic Regression to enforce monotonic relationships (higher engagement = higher probability).
    # RandomForest can dangerously overfit on 7 rows by mapping exact numerical boundaries (e.g. 3 likes exactly = trending).
    model = LogisticRegression(class_weight='balanced', random_state=42)
    # If the database is completely one-sided (e.g., all 1s or all 0s), skip ML and use heuristics
    try:
        model.fit(X_scaled, y)
        ml_active = True
    except ValueError:
        logging.warning("Only one class present in training data. Defaulting to exact heuristic ranking.")
        ml_active = False

    # 4. Predict probabilities across recent posts
    # Increased to 99999 hours (effectively infinite) so older posts aren't hard-filtered out during testing
    recent_mask = df['age_hours'] <= 99999
    recent_df = df[recent_mask].copy()

    if recent_df.empty:
        logging.warning("No recent posts found in the last 7 days. Reverting to all-time posts.")
        recent_df = df.copy()

    if ml_active:
        X_recent = scaler.transform(recent_df[features])
        probs = model.predict_proba(X_recent)
        recent_df['trending_prob'] = probs[:, 1]
    else:
        # Fallback fake probability based entirely on ranking
        recent_df['trending_prob'] = recent_df['decayed_score'] / (recent_df['decayed_score'].max() + 1e-9)
    
    # Get top recommended post using strict sorting
    top_post = recent_df.sort_values(by=['trending_prob', 'decayed_score'], ascending=[False, False]).iloc[0]

    # 5. Output to JSON for Symfony Backend
    recommended_data = {
        'post_id': int(top_post['id']),
        'title': top_post['title'],
        'trending_probability': float(top_post['trending_prob']),
        'engagement_score': float(top_post['engagement_score']),
        'updated_at': now.strftime("%Y-%m-%d %H:%M:%S")
    }

    with open(JSON_OUTPUT_PATH, 'w') as f:
        json.dump(recommended_data, f, indent=4)

    logging.info(f"Model selection complete! Recommended Post ID: {recommended_data['post_id']} (Prob: {recommended_data['trending_probability']:.2f})")

def main():
    logging.info("Starting Auto-Recommendation Engine...")
    while True:
        try:
            run_ml_pipeline()
        except Exception as e:
            logging.error(f"Pipeline crashed during execution: {e}")
        
        logging.info("Sleeping for 5 minutes before next evaluation...\n")
        time.sleep(10) # 5 minutes

if __name__ == "__main__":
    main()
