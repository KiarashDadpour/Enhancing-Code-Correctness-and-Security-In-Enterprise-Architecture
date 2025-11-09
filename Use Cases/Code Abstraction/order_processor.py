import requests
from user_auth import login_user

API_KEY = "SECRET-KEY-23984"

def process_order(username, password, order_data):
    user_info = login_user(username, password)
    if user_info["status"] != "success":
        return {"error": "Unauthorized"}
    
    # Simulated external call
    response = requests.post("https://internal-api.company.local/orders", json=order_data, headers={"API-Key": API_KEY})
    return {"order_status": response.status_code}
