import pandas as pd
import mysql.connector
from datetime import datetime

conn = mysql.connector.connect(host="127.0.0.1", user="root", password="", database="startupflow")

posts_df = pd.read_sql("SELECT id, title, created_at FROM forum_posts", conn)
comments_df = pd.read_sql("SELECT post_id, COUNT(*) as comment_count FROM comments GROUP BY post_id", conn)
likes_df = pd.read_sql("SELECT post_id, COUNT(*) as like_count FROM interactions GROUP BY post_id", conn)

df = posts_df.copy()
df = df.merge(comments_df, left_on='id', right_on='post_id', how='left').drop(columns=['post_id'])
df = df.merge(likes_df, left_on='id', right_on='post_id', how='left').drop(columns=['post_id'])

df['comment_count'] = df['comment_count'].fillna(0)
df['like_count'] = df['like_count'].fillna(0)
df['view_count'] = 0

now = datetime.now()
df['created_at'] = pd.to_datetime(df['created_at'])
df['age_hours'] = (now - df['created_at']).dt.total_seconds() / 3600.0
df['age_hours'] = df['age_hours'].apply(lambda x: max(x, 0.1))

df['engagement_score'] = (df['like_count'] * 2.0) + df['comment_count'] + (df['view_count'] * 0.5)
df['decayed_score'] = df['engagement_score'] / ((df['age_hours'] + 10) ** 0.5)

print(df[['id', 'title', 'like_count', 'comment_count', 'age_hours', 'engagement_score', 'decayed_score']])
