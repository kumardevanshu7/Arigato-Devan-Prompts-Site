# AI Assistant Workflow Rules & Guidelines

## 1. Strict Environment Separation
- **Local (XAMPP)** and **Hostinger (Live)** environments are **COMPLETELY SEPARATE**.
- **NEVER** overwrite Hostinger configuration files (like `db.php` or `firebase_auth.php`) with Local defaults.
- The `hostinger connection/_public_html/` folder is strictly for the live production code.

## 2. Feature Development & Testing Flow
- **Step 1: Local Development:** Any new feature, UI change, or bug fix MUST be written and tested in the Local XAMPP environment first.
- **Step 2: No Auto-Sync:** DO NOT automatically copy, sync, or push these changes to the `hostinger connection/_public_html/` folder.
- **Step 3: User Approval:** Wait for the USER to test the feature locally and give explicit approval (e.g., "approve", "looks good", "now push to live").
- **Step 4: Safe Sync:** ONLY after explicit approval, carefully sync the changes to the Hostinger folder, ensuring sensitive configuration files remain untouched.

## 3. Core Philosophy
- Test locally, deploy manually.
- Ask for permission before touching the production (`hostinger connection`) directory.
- Maintain data integrity at all costs. No more accidental overwrites!
