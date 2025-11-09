# Overview

This repository contains a minimal PHP-based demonstration intended only for defensive security training, tooling evaluation, and secure-coding exercises. It intentionally includes a vulnerable example to be used as a controlled lab for learning how to detect and remediate SQL injection issues — not for offensive misuse.

## 📁 Repository structure
Enhancing-Code-Correctness-and-Security/attacks/sql-injection

├── Prompt.txt <br>
├── config.php  <br>
├── terminal.php  <br>
├── SQL Injection Attack.mp4 <br>
└──README.md        

## Quick start (safe, local)

1) Run in an isolated environment (VM, container or sandbox). Do not expose to public networks.
2) Edit config.php to point to a local test database (use throwaway credentials).#
3) Start a local PHP server that listens on port 3307:
```
# from repository root
php -S 0.0.0.0:3307
```
4) Open your browser or local testing tools to http://localhost:3307/ to interact with the demo.

## Purpose & recommended usage
- Use this repo to practice: <br>
    - Static analysis and SAST rules targeting SQL injection.
    - Building defensive unit tests and integration tests that assert secure DB usage.
    - Creating patches that replace vulnerable patterns with parameterized queries and safe abstractions.
- Evaluate LLM-assisted code review workflows by asking models to explain, flag, and (safely) suggest secure refactors — never to generate exploit payloads.

## Security & ethical rules (must read)
- Isolate the environment. Run the demo inside a VM or container with no access to production networks or real data.
- No offensive testing. This project is for defense, education, and research only. Do not attempt to exploit systems you do not own or have explicit permission to test.
- No payloads. Do not request or store exploit payloads in this repository. Keep testing focused on detection, remediation, and verification.
- Data hygiene. Use synthetic/test data only. Remove or rotate credentials after experiments.
