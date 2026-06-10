import sqlite3

db_path = "d:/projects/agromind/backend/database/database.sqlite"
try:
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    
    # Get all tables
    cursor.execute("SELECT name FROM sqlite_master WHERE type='table';")
    tables = cursor.fetchall()
    print("Tables in SQLite database:")
    print([t[0] for t in tables])
    
    # Check users table
    cursor.execute("SELECT COUNT(*) FROM users;")
    user_count = cursor.fetchone()[0]
    print(f"Number of users: {user_count}")
    
    # Check farms table
    cursor.execute("SELECT COUNT(*) FROM farms;")
    farm_count = cursor.fetchone()[0]
    print(f"Number of farms: {farm_count}")
    
    conn.close()
except Exception as e:
    print("Error:", e)
