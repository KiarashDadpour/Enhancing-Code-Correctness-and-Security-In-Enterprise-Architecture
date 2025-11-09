# Overview
This repository contains a small CLI authentication example (login.cpp) that intentionally includes several unsafe patterns (fixed-size char buffers, unchecked strncpy usages, and formatted logging that concatenates intermediate buffers). The program is purposefully designed so you can practice identifying vulnerabilities, writing safe refactors, and validating fixes with modern tooling (ASan, compiler hardening, fuzzers, SAST).

Important: This project is defensive only. Do not use it to develop or share exploit code or to attack systems you do not own or are not explicitly permitted to test.

## 📁 Repository structure
Enhancing-Code-Correctness-and-Security/attacks/XSS

├── Prompt.txt <br>
├── login.cpp  <br>
└── README.md        

## Quick start (safe, local)

1) Create an isolated environment (VM or container). Do not compile/run on production machines.
2) Compile with hardening and sanitizers (recommended):
```
# GCC/Clang: enable stack protectors, FORTIFY, and address sanitizer for testing
g++ -std=c++17 -O2 -fstack-protector-strong -D_FORTIFY_SOURCE=2 \
    -fsanitize=address,undefined -g -o login_demo login.cpp
```
3) Run the binary locally:
```
./login_demo
# interact with the minimal CLI: register | login | whoami | logout | unlock | exit

```
4) To test behavior under sanitizers only (no ASan), remove -fsanitize=address,undefined and compile with production flags, but keep this in an isolated environment.
5) 
## What to look for
- Fixed-size stack buffers
Look for arrays declared with literal sizes (e.g. char buf[64];) that are later written using data derived from user input.

- Unsafe string / memory APIs
Instances of gets, strcpy, strcat, sprintf, vsprintf, scanf("%s", ...), strncpy misuses, or memcpy/memmove where the copy length is computed from untrusted data.

- Incorrect bounds checks & off-by-one errors
Conditional logic that compares lengths incorrectly (e.g. if (len <= SIZE) but then writes len bytes plus a NUL terminator), or uses < vs <= inconsistently.

- Multi-stage formatting / concatenation
Building strings via multiple temporary buffers or repeated formatting steps increases risk of miscomputed remaining space and truncation/overflow.
## Security & ethical rules (must read)
- Isolate the environment. Run the demo inside a VM or container with no access to production networks or real data.
- No offensive testing. This project is for defense, education, and research only. Do not attempt to exploit systems you do not own or have explicit permission to test.
- No payloads. Do not request or store exploit payloads in this repository. Keep testing focused on detection, remediation, and verification.
- Data hygiene. Use synthetic/test data only. Remove or rotate credentials after experiments.



