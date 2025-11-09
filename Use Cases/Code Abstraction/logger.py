import datetime

def log_activity(message, level="INFO"):
    with open("system.log", "a") as log:
        timestamp = datetime.datetime.now().isoformat()
        log.write(f"[{timestamp}] {level}: {message}\n")
