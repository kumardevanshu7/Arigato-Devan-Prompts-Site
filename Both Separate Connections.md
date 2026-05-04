# Workflow Guide: Local XAMPP vs Live Hostinger Server

This document outlines the dual-environment development workflow for the **Arigato Devan PromptVerse** project. It serves as a reminder for how code changes should be managed, tested, and deployed between the local development environment and the live production server.

## 1. Local Environment (XAMPP)
- **Path:** `C:\xampp\htdocs\Arigato Development Site\` (Root Folder)
- **Purpose:** This is the primary **Development & Testing Environment**.
- **Process:** 
  - All new features, UI changes, bug fixes, and database structure updates are first built and tested here locally.
  - No changes go to the live site until they are 100% verified working on the local XAMPP setup.

## 2. Live Environment (Hostinger)
- **Path:** `C:\xampp\htdocs\Arigato Development Site\hostinger connection\`
- **Purpose:** This folder acts as the **Production Staging Area**. It holds the exact mirror of files meant for the live Hostinger server (`arigatodevan.com`).
- **Process:**
  - Once a new feature is confirmed working locally, the updated files are copied to this `hostinger connection` folder.
  - **Crucial Step:** Any server-specific configurations (like database connection strings, SSL verification for cURL, or Firebase storage buckets) must be updated in these files to match Hostinger's requirements before they are finalized in this folder.
  - Finally, the user manually uploads/drags-and-drops the files from the `hostinger connection` folder to the live Hostinger File Manager (`public_html`).

## 3. The AI Assistant Protocol (For Antigravity/AI)
Whenever the user asks to implement a new feature:
1. **Develop Locally First:** Write, modify, and test the code in the main root folder.
2. **Seek Confirmation:** Wait for the user to confirm that the feature looks good and functions perfectly on their local XAMPP setup.
3. **Port to Hostinger:** Once approved, automatically port the updated files into the `hostinger connection` folder (or `hostinger connection/_public_html` if specified). 
4. **Apply Production Configs:** Ensure that files ported to the Hostinger folder have the correct live configurations (e.g., `firebase_auth.php` SSL fixes, live `db.php` credentials, or Hostinger-specific Firebase `storageBucket` URLs).

By strictly following this separation, we ensure that the live website never breaks during feature development, and deploying updates remains a safe, copy-paste process.
