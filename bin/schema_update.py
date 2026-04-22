import mysql.connector

conn = mysql.connector.connect(host="127.0.0.1", user="root", password="", database="startupflow")
cursor = conn.cursor()

try:
    cursor.execute("ALTER TABLE users ADD COLUMN face_encoding TEXT NULL")
    conn.commit()
    print("Successfully added face_encoding to users table.")
except mysql.connector.Error as err:
    print(f"Error: {err}")

cursor.close()
conn.close()
