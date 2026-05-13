# Arigato Devan PromptVerse - Comprehensive Codebase Blueprint

**CRITICAL INSTRUCTION FOR AI AGENTS:**
Read this document from top to bottom before executing any task. This is the **Master Blueprint** of the Arigato Development Site (PromptVerse). It details every table, logic flow, file purpose, styling system, and deployment pipeline. You do not need to read individual files to understand the architecture; it is all here.

---

## 1. Executive Summary & Tech Stack
*   **Project Goal:** A platform for users to view, unlock, and save AI-generated image prompts (specifically "Couple AI Content").
*   **Frontend:** HTML5, Vanilla JavaScript (`script.js`), Custom Vanilla CSS (`style.css`).
    *   *Design Aesthetics:* Vibrant, "Comic" style. Uses glassmorphism (backdrop-filter), bold text (`Outfit` font), distinct box shadows (`4px 4px 0px var(--text-color)`), and smooth micro-animations.
*   **Backend:** PHP 8+ using PDO for secure MySQL interactions. No heavy frameworks (like Laravel).
*   **Database:** MySQL.
*   **Authentication:** Firebase Authentication (Google OAuth) paired with PHP Sessions.
*   **Hosting Pipeline:** Developed locally in `C:\xampp\htdocs\Arigato Development Site\`. Deployed via manual copy/sync to Hostinger (`hostinger connection/_public_html/`).

---

## 2. Directory & Workspace Structure
The user works out of **one active workspace** with a dual-folder synchronization workflow:

1.  **`c:\xampp\htdocs\Arigato Development Site\`** -> *Local Development Root*
    *   This is where local XAMPP testing occurs.
2.  **`c:\xampp\htdocs\Arigato Development Site\hostinger connection\_public_html\`** -> *Production Mirror*
    *   This folder perfectly mirrors the root folder but is used to upload files to the live Hostinger server. 
    *   **CRITICAL RULE:** Whenever you modify a PHP, JS, or CSS file in the root directory, you **MUST** ensure the exact same change is made in the `hostinger connection/_public_html` directory to keep the live site synced.

### Media Directories (Both Local & Prod)
*   `/uploads/` - High-res uploaded prompts (WebP).
*   `/landingpics/` - Static filmstrip images for the logged-out homepage.
*   `/profiledp/` - User-uploaded avatars.
*   `/blogpostimg/` - Images used in blog posts.
*   `/progresspics/` - Images for the vertical progress timeline page.

---

## 3. Database Schema Blueprint
Connection is managed via `db.php` using PDO. The `utf8mb4` charset is standard.

### Core Tables
1.  **`users`**
    *   `id` (INT PK, Auto-increment)
    *   `uid` (VARCHAR) - The unique Firebase User ID.
    *   `email` (VARCHAR)
    *   `username` (VARCHAR)
    *   `avatar` (VARCHAR)
    *   `role` (ENUM: 'user', 'admin') - Default 'user'.
    *   `onboarding_complete` (TINYINT) - 1 if username is set.
    *   `created_at` (TIMESTAMP)
2.  **`prompts`** (The core content entity)
    *   `id` (INT PK)
    *   `title` (VARCHAR)
    *   `tag` (VARCHAR) - Comma-separated tags (e.g., 'aesthetic, anime, cute').
    *   `image_path` (VARCHAR)
    *   `prompt_text` (TEXT) - The hidden text users want to unlock.
    *   `prompt_type` (ENUM) - Values: `secret_code`, `unreleased`, `insta_viral`, `already_uploaded`.
    *   `unlock_code` (VARCHAR) - A 6-character code (used only if `prompt_type` = `secret_code`).
    *   `reel_link` (VARCHAR) - Instagram reel link referencing the prompt.
    *   `likes_count` (INT)
    *   `created_at` (TIMESTAMP)
3.  **`unlocked_prompts`**
    *   `id`, `user_id` (FK -> users), `prompt_id` (FK -> prompts), `unlocked_at`.
    *   *Purpose:* Tracks which users have successfully bypassed the unlock mechanisms.
4.  **`likes`** & **`saved_prompts`**
    *   `id`, `user_id` (FK), `prompt_id` (FK), `created_at`.
5.  **`blogs`**
    *   `id`, `title`, `content` (HTML/TEXT), `image_path`, `author_id`, `likes_count`, `created_at`.
6.  **`blog_comments`** & **`blog_likes`**
    *   Similar linking tables for blog engagement.

---

## 4. Deep Dive: Prompt Types & Unlock Logic
The primary UX revolves around a modal in `script.js` that intercepts clicks on prompt cards. Depending on the `prompt_type` from the DB, `script.js` renders a different "Lock Challenge". All verifications are sent via POST to `unlock.php`.

### A. Secret Code (`secret_code`)
*   **UI:** Shows a text input field asking for a 6-letter code.
*   **Logic:** User enters text -> JS POSTs to `unlock.php (action=verify)` -> PHP checks `SELECT prompt_text FROM prompts WHERE LOWER(unlock_code) = LOWER(?)`.
*   **Fallback:** Shows a "Want Code? Watch Reel" button linking to Instagram.

### B. Unreleased Love Tap (`unreleased`)
*   **UI:** Shows a progress bar and a "Tap to Love" button.
*   **Logic:** 
    *   Logged-in users need 20 taps. Guests need 90 taps.
    *   Opening modal triggers `unlock.php (action=init_love)` to set a `$_SESSION['urp_start_{id}']` timestamp.
    *   When taps are complete, JS POSTs to `unlock.php (action=unreleased)`.
    *   **Anti-Bot Security:** PHP validates that `time() - start_time` is AT LEAST 2 seconds. If too fast, it rejects the unlock.

### C. Insta Viral Math Challenge (`insta_viral`)
*   **UI:** Shows a simple addition problem (e.g., "7 + 3 + 9 + 1 = ?") and 4 multiple-choice buttons.
*   **Logic:**
    *   Opening modal triggers `unlock.php (action=get_challenge)`. PHP generates 4 random numbers, calculates the sum, stores the answer in `$_SESSION['iv_ch_{id}']`, and sends the numbers back.
    *   JS renders the buttons. Clicking the correct one POSTs to `unlock.php (action=insta_viral)` with `user_answer`.
    *   PHP compares `user_answer` to the session. If correct, unlocks prompt text.

### D. Already Uploaded (`already_uploaded`)
*   **UI:** Shows a basic "Tap to Unlock" button.
*   **Logic:** Requires exactly 9 taps. Bypasses strict security; posts to `unlock.php (action=already_uploaded)`.

---

## 5. Security & Authentication Flow
### Firebase to PHP Auth (`firebase_auth.php`)
1.  **Frontend (`script.js` or `login.php` inline JS):** 
    *   User clicks "Login with Google".
    *   Firebase SDK invokes `signInWithPopup(auth, provider)`.
    *   Returns a Firebase `idToken`.
2.  **Backend Verification:**
    *   JS sends `idToken` via POST to `firebase_auth.php`.
    *   PHP uses `file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token)` to verify validity.
    *   If valid, PHP queries the `users` table for the matching `uid`.
    *   If user exists -> logs them in (Sets `$_SESSION['user_id']`).
    *   If user does NOT exist -> creates a new DB record -> logs them in.
3.  **Onboarding Guard:**
    *   At the top of `index.php` and `gallery.php`:
    *   `if (isset($_SESSION['user_id']) && empty($_SESSION['onboarding_complete'])) { header("Location: onboarding.php"); }`
    *   Ensures new users pick a username before browsing.

---

## 6. CSS & UI Design System (`style.css`)
The UI relies heavily on a custom CSS variable system. No Tailwind or Bootstrap is used.

**Core Variables:**
```css
:root {
    --bg-color: #fdfbf7;        /* Creamy white background */
    --text-color: #2d2a35;      /* Deep dark purple/black text */
    --primary-color: #e6d7ff;   /* Soft lavender/purple */
    --primary-dark: #c6adfa;    
    --secondary-color: #fff1b8; /* Soft warm yellow */
    --border-color: #eae3f2;
    --card-bg: #ffffff;
    --border-width: 3px;        /* Thick comic borders */
    --shadow-comic: 4px 4px 0px var(--text-color); /* Hard, non-blurred shadows */
    --shadow-comic-hover: 6px 6px 0px var(--text-color);
    --font-main: "Outfit", sans-serif;
}
```
**Key UI Components:**
*   **Buttons (`.comic-btn`):** Use strict borders and `--shadow-comic`. Active states use `transform: translate(4px, 4px)` and reduce box-shadow to `0px` to simulate a physical button press.
*   **Navigation Dropdown (`.nav-dropdown`):** Custom JS/CSS dropdown showing prompt categories with "ACTIVE" or "SOON" tags.
*   **Backgrounds (`.scroll-bg-container`):** Multi-layered parallax scrolling wallpapers powered by `window.addEventListener('scroll')` in inline JS.

---

## 7. File-by-File Breakdown
### General Pages
*   **`index.php`**: Dual-purpose homepage. If logged out: Shows landing hero, filmstrip animation, and feature comparisons. If logged in: Shows Swipeable Tinder-like card stack of `secret_code` prompts.
*   **`gallery.php`**: Standard grid layout showing ALL prompts. Includes tag filters (All, Aesthetic, Anime, etc) and a search bar.
*   **`progress.php`**: The "Journey" page. A vertical timeline with polaroid cards showing Instagram follower milestones.
*   **`login.php`**: Minimalist UI housing the Firebase Google Login button.

### Content Segregation Pages
*   **`secret_code.php`**, **`unreleased.php`**, **`insta_viral.php`**, **`already_uploaded.php`**: These pages are near-clones of `index.php` (swipeable card stack) but query the database specifically for their respective `prompt_type`. 

### Admin Architecture (Guarded by `$_SESSION['role'] === 'admin'`)
*   **`dashboard.php`**: The central admin hub. Contains links to all admin functions.
*   **`analytics.php`**: Uses Chart.js to render real-time graphs (Likes per prompt, User Growth line charts, Prompts uploaded).
*   **`manage_prompts.php`**: Data table of all prompts. Allows editing/deleting.
*   **`upload_prompt.php`**: The form to add a new prompt. Supports image uploads (auto-converts to WebP), tags, and setting the `prompt_type`.
*   **`edit_prompt.php`**: Populates the upload form with existing data for modifications.
*   **`blog_admin.php`**, **`blog_create.php`**: Standard CRUD interface for blog management using TinyMCE (or basic textarea).

### API / Action Handlers
*   **`unlock.php`**: The most complex backend file. Handles rate limiting, session timers, math validations, and inserts into `unlocked_prompts`.
*   **`like.php`**: Toggles likes. Inserts/Deletes from `likes` table and updates `prompts.likes_count`.
*   **`save_prompt.php`**: Toggles saving to the `saved_prompts` table.
*   **`user_data.php`**: Handles profile updates (username, avatar changes).
*   **`upload_editor_image.php`**: Handles async image uploads originating from the blog text editor.

---

## 8. External Connections & APIs
1.  **Google Analytics (`gtag.php`):**
    *   Tracking ID: `G-1B4V97JP7T`.
    *   Included at the very end of the `<head>` block in all user-facing `.php` files via `<?php include_once 'gtag.php'; ?>`.
2.  **Firebase:**
    *   Client config located inside `script.js` (and occasionally inline in `login.php`).
    *   Relies on Google's `oauth2.googleapis.com` for token verification in PHP.
3.  **Font Awesome & Boxicons:** Loaded via CDN for UI icons.
4.  **Google Fonts:** `Outfit`, `Lora`, `Playfair Display` loaded via CDN.

---

## 9. Common Bug Vectors (Read Before Debugging)
*   **Headers Already Sent:** `db.php` is required on line 3 of almost every file. **NEVER** place HTML, whitespace, or `echo` statements inside `db.php` or at the very top of files before `session_start()`.
*   **Sync Issues:** The biggest point of failure. If an AI modifies `index.php`, but forgets to modify `hostinger connection/_public_html/index.php`, the live site breaks. ALWAYS duplicate your file writing actions.
*   **JavaScript Closures:** The `script.js` modal logic registers event listeners inside a `.forEach` loop over cards. Modifying variables like `taps` requires careful scoping or DOM dataset usage to prevent cross-card contamination.
*   **CSS Specificity:** Because the CSS relies on strict class names and heavy visual variables, avoid using inline styles unless strictly necessary for dynamic content (like progress bar widths).

---
*Generated by Antigravity AI - System Documentation Module*
