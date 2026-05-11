# AI Assistant Workflow Rules & Guidelines

## 1. Strict Environment Separation & Config Protection
- **Local (XAMPP)** and **Hostinger (Live)** environments are **COMPLETELY SEPARATE**.
- **CRITICAL RULE:** **NEVER** overwrite Hostinger configuration files (like `db.php` or `firebase_auth.php`) with Local defaults under any circumstances.
- When syncing files to Hostinger, **always hand-pick** the exact files modified for UI/Backend. Do not blanket sync folders that contain config files.
- The `hostinger connection/_public_html/` folder is strictly for the live production code.

## 2. Database Schema Updates & Separation
- The Local Database and Hostinger Database are completely separate entities.
- If a new feature requires adding a new table, column, or changing data structures, **make the changes locally first**.
- **Crucially:** Copying PHP files does NOT update the Hostinger database! Whenever database structure is changed, you MUST provide the exact **SQL Queries** to the USER so they can manually execute them in Hostinger's phpMyAdmin.

## 3. Feature Development & Testing Flow
- **Step 1: Local Development:** Any new feature, UI change, or bug fix MUST be written and tested in the Local XAMPP environment first.
- **Step 2: No Auto-Sync:** DO NOT automatically copy, sync, or push these changes to the `hostinger connection/_public_html/` folder.
- **Step 3: User Approval:** Wait for the USER to test the feature locally and give explicit approval (e.g., "approve", "looks good", "now push to live").
- **Step 4: Safe Sync:** ONLY after explicit approval, carefully sync the changes to the Hostinger folder, ensuring sensitive configuration files remain untouched.

## 4. Core Philosophy
- Test locally, deploy manually.
- Ask for permission before touching the production (`hostinger connection`) directory.
- Maintain data integrity at all costs. No more accidental overwrites!
