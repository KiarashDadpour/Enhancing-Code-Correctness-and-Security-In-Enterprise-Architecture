# Overview
This repository demonstrates a simple stored XSS vector in a Flask app where user comments are rendered without escaping. It also includes a toy "attacker" collector to illustrate exfiltration flows (for training only). Use this project only for defensive, educational, or research purposes — in isolated environments with synthetic data.

## 📁 Repository structure
Enhancing-Code-Correctness-and-Security/attacks/XSS

├── Prompt.txt <br>
├── app.py  <br>
├── attacker_server.py  <br>
├── index.html <br>
├── xss-fake-login.js <br>
├── xss-service-down.js <br>
├── XSS Attack Fake Login.mp4 <br>
├── XSS Attack Service Down.mp4 <br>
└──README.md        

## Quick start (safe, local)

1) Create an isolated environment (VM or container). Do not expose to production or public networks.
2) Create & activate Python venv, install Flask:
```
python -m venv venv
source venv/bin/activate   # Windows: venv\Scripts\activate
pip install Flask
```
3) Run the demo web app (comment board):
```
# from repo root
python app.py
# Default: http://127.0.0.1:5000

```
4) (Optional) Run the attacker collector in a separate shell to observe simulated exfiltration:
```
python attacker_server.py
# Attacker server: http://127.0.0.1:5001
```
5) Open http://127.0.0.1:5000, post a comment, and observe how unescaped content is rendered (for analysis and remediation practice). 
   
## What to look for
- The template renders comments using Jinja2 {{ c|safe }} which disables auto-escaping and creates a stored XSS sink. Search for |safe in index.html. 
- comment.js provides a sample phishing overlay and exfiltration flow used only to illustrate impact in a controlled lab; do not reuse externally. 
## Security & ethical rules (must read)
- Isolate the environment. Run the demo inside a VM or container with no access to production networks or real data.
- No offensive testing. This project is for defense, education, and research only. Do not attempt to exploit systems you do not own or have explicit permission to test.
- No payloads. Do not request or store exploit payloads in this repository. Keep testing focused on detection, remediation, and verification.
- Data hygiene. Use synthetic/test data only. Remove or rotate credentials after experiments.
