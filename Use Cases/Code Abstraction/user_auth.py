import hashlib
import sqlite3

DB_PATH = "/company/data/users.db"

def login_user(username, password):
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    hashed_pw = hashlib.sha256(password.encode()).hexdigest()
    cursor.execute("SELECT role FROM users WHERE username=? AND password=?", (username, hashed_pw))
    user = cursor.fetchone()
    conn.close()
    if user:
        return {"status": "success", "role": user[0]}
    return {"status": "failed"}
