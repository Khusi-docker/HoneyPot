<h1>IoT Unauthorized Access Detection & Honeypot Redirection System</h1>

This project provides a security framework for IoT devices to detect unauthorized users and redirect them to a honeypot. It performs IP spoofing checks, authentication validation, malware detection, and scan detection to ensure only authorized access is allowed. The system uses **Hiotpot** and **HoneyIoT4** architectures to detect threats and divert attackers into a controlled environment.

<h2>🔒 Project Objective</h2>

To protect IoT devices by:
- Detecting unauthorized or suspicious users
- Validating user identity
- Identifying spoofed IP addresses
- Detecting malware payloads
- Detecting scanning and reconnaissance
- Redirecting attackers into the honeypot

<h2>🏗 Architecture Used</h2>

<h3>1. Hiotpot</h3>
- Lightweight IoT honeypot  
- Supports MQTT, CoAP, HTTP  
- Collects attacker logs  

<h3>2. HoneyIoT4</h3>
- Advanced multi-device IoT honeypot  
- Captures scanning, brute-force, and exploitation attempts  

---

<h2>⚙️ Features</h2>

### ✔️ IP Spoofing Detection
- Detects mismatched IP headers  
- Flags abnormal packet patterns  

### ✔️ Authentication Validation
- Checks user credentials  
- Blocks brute-force attempts  

### ✔️ Malware Detection
- Scans payloads for malware signatures  
- Detects unauthorized firmware/code injection  

### ✔️ Scan Detection
- Identifies port scanning and heavy probing  
- Monitors abnormal traffic patterns  

### ✔️ Honeypot Redirection
- Redirects suspicious users to Hiotpot/HoneyIoT4  
- Collects logs safely for analysis


### Decision Flow
<img width="1186" height="676" alt="Screenshot 2026-02-21 022621" src="https://github.com/user-attachments/assets/6043fd7f-98e2-491f-89b1-efeeaadc8f4d" />

### Individual Features Flow
<img width="910" height="362" alt="Screenshot 2026-05-17 135741" src="https://github.com/user-attachments/assets/5654b385-aecd-41ae-bbba-0f86da1a168e" />



