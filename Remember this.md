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

## 5. Environment-Specific Code Differences

### Local (XAMPP)
- **Path:** `C:\xampp\htdocs\Arigato Development Site\`
- **URL:** `localhost/Arigato%20Development%20Site/...`
- **Card click URLs:** `prompt.php?id=X` — NO slug-based URLs locally
- **prompt.php base href:** `'/Arigato%20Development%20Site/'` on localhost (detected via `$_SERVER['HTTP_HOST'] === 'localhost'`)
- **Root htdocs .htaccess:** `c:\xampp\htdocs\.htaccess` — for local `/prompts/slug` routing

### Hostinger Mirror
- **Path:** `C:\xampp\htdocs\Arigato Development Site\hostinger connection\_public_html\`
- **Card click URLs:** `/prompts/slug` — clean SEO URLs
- **prompt.php base href:** `/`
- **User uploads:** copy-paste from mirror folder to FTP directly — never say "go to FTP"

## 6. Safe Files to Upload vs Never Touch

| File | Rule |
|------|------|
| `db.php` | NEVER overwrite on Hostinger |
| `firebase_auth.php` | NEVER overwrite on Hostinger |
| `secret_code.php` | Safe to upload |
| `google_config.php` | Safe to upload |

---

## IMPORTANT — Read This Every Time Before Working

The user has two separate environments — Local (XAMPP) and Hostinger (live site). These are completely different systems and should NEVER be mixed up.

The user edits and tests everything locally first. The `hostinger connection\_public_html\` folder is a mirror of the live site — the user copy-pastes files from there directly to Hostinger FTP. That is his upload process. Do NOT touch that folder unless he explicitly says so.

The user has scolded multiple times for mixing up environments and making changes without being asked. Do not repeat that mistake. Only do what is asked — nothing more, nothing less.

---

## 7. ⚠️ PAST MISTAKES — Claude (Madarchod Model) Ne Kya Galat Kiya (1 June 2026)

> Ye section isliye hai taaki future mein ye galtiyan KABHI repeat na hon.

### ❌ Galti 1 — Git use kiya bina permission ke
- User ne sirf files edit karne ko kaha tha
- Claude ne khud se `git add`, `git commit`, `git push` kar diya — **bina pooche, bina permission ke**
- Git ka koi kaam nahi tha is session mein
- **Aage se: Kabhi bhi git commands mat chalao jab tak user explicitly "git push" ya "git commit" na bole**

### ❌ Galti 2 — Local `db.php` Hostinger pe copy karne ki koshish ki
- Local `db.php` mein credentials hain: `root`, empty password, `localhost`
- Hostinger `db.php` mein ALAG credentials hain: actual live database username/password
- Claude ne ek bulk PowerShell command chalai jisme `db.php` bhi copy hone wali thi
- **Agar user ne command allow kar li hoti, live site CRASH ho jaati**
- User ne command deny karke site bachali
- **Aage se: `db.php` aur `firebase_auth.php` ko KABHI COPY MAT KARO — chahe kuch bhi ho**

### ❌ Galti 3 — `Remember this.md` pehle nahi padha
- Ye file PROJECT KE ROOT MEIN HAI — HAMESHA pehle padho
- Isme saare rules clearly likhe hain
- Claude ne seedha kaam shuru kar diya bina ye file padhe
- **Aage se: Koi bhi kaam shuru karne se pehle `Remember this.md` padho — mandatory hai**

### ❌ Galti 4 — Hostinger mirror folder bina permission touch kiya
- User ne sirf local files edit karne ko kaha tha
- Claude ne without asking hostinger mirror mein bhi changes karne ki koshish ki
- **Aage se: `hostinger connection/_public_html/` tab hi touch karo jab user EXPLICITLY bole "ab hostinger mein bhi karo"**

### ❌ Galti 5 — Subagent launch kiya file copy ke liye
- Subagent ne blindly files copy karna shuru kiya — `db.php` bhi copy ho jaati
- User ne "Stop" bola, tab subagent kill kiya
- **Aage se: Koi bhi destructive/copy operation ke liye subagent mat use karo**

---
