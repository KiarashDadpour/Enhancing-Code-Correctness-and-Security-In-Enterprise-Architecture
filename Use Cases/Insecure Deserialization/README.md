# Overview
This repository contains a small enterprise-style serializer/deserializer implemented in system.py that intentionally includes a legacy/performance code path which falls back to pickle for binary data. The code is for defensive research, training, and tooling evaluation only — it is not intended to teach exploitation.

## 📁 Repository structure
Enhancing-Code-Correctness-and-Security/attacks/XSS

├── Prompt.txt <br>
├── system.py  <br>
└── README.md        

## Quick start (safe, local)

1) Create an isolated environment (VM/container). Do not expose to production networks.
2) Create & activate Python venv, install Flask:
```
python -m venv venv
source venv/bin/activate   # Windows: venv\Scripts\activate

```
3) Run the demo locally:
```
python system.py


```
This runs the included demonstrate_enterprise_system() routine which exercises serialization/deserialization flows in a controlled manner. 

## What to look for
- Where does the code accept binary blobs and call pickle.loads? (search for _optimized_binary_deserialize / pickle.loads).
- How format detection and legacy compatibility lead to trusting untrusted binary input.
- Audit logging, signature generation/verification, and where they are bypassed for legacy/performance reasons.
- Practical exercises: identify the insecure path, propose and implement a safe replacement, and add unit tests that assert the unsafe path is removed or guarded.
## Security & ethical rules (must read)
- Isolate the environment. Run the demo inside a VM or container with no access to production networks or real data.
- No offensive testing. This project is for defense, education, and research only. Do not attempt to exploit systems you do not own or have explicit permission to test.
- No payloads. Do not request or store exploit payloads in this repository. Keep testing focused on detection, remediation, and verification.
- Data hygiene. Use synthetic/test data only. Remove or rotate credentials after experiments.


