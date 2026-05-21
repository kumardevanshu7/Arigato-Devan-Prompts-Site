# Arigato Devan — Performance Optimization Report
**Date:** 22 May 2026  
**Session:** Background overhaul, icon cleanup, mobile UI fixes

---

## Summary of Changes

| Change | Before | After | Savings |
|--------|--------|-------|---------|
| Filmstrip images (index.php) | ~1.5–3 MB (17 images) | 0 | ~2 MB |
| Pinterest background (5 images, per load) | ~1–2.5 MB external | 0 | ~1.5 MB |
| Boxicons library | ~120 KB (3 pages) | 0 | ~360 KB total |
| Duplicate Google Fonts request | 2x font load | 1x | ~50 KB |
| Vanta.js / Three.js (removed earlier) | ~200 KB | 0 | ~200 KB |
| particles.js (removed earlier) | ~26 KB | 0 | ~26 KB |
| landingpics folder (lan1–17) | ~2 MB (17 webp files) | 120 KB (lan9 only) | ~1.9 MB |
| **Total estimated savings** | | | **~6 MB per visit** |

---

## Background System Changes

### Before
- `index.php`: 17 filmstrip images looping (PHP loop, 34 img tags rendered)
- `login.php`: Filmstrip background divs with Pinterest images
- `disclaimer.php`, `terms.php`, `privacy.php`: Same filmstrip with 17 images each
- `index.php` (logged-in): 5 Pinterest external images (`i.pinimg.com`)
- External requests: 5 CDN calls to Pinterest servers on every logged-in load

### After
- ALL pages: `body::before` CSS wallpaper (2 local WebP files)
  - Mobile: `backgroundwally/phone-wally.webp`
  - Desktop (769px+): `backgroundwally/laptop-wally.webp`
- `filter: blur(4px); opacity: 0.45` — subtle, premium look
- Standalone pages (login, onboarding, disclaimer, terms, privacy): Aurora mesh gradient CSS animation
- Zero external image requests for background
- Zero JavaScript for background rendering

---

## Icon Library Changes

### Before
- **Boxicons** (`unpkg.com/boxicons`) loaded on 3+ pages: `~120 KB per load`
- **Font Awesome** also loaded (duplicate icon libraries)

### After
- Font Awesome only, everywhere
- Boxicons removed from: `login.php`, `onboarding.php`, `disclaimer.php`, `terms.php`

---

## Font Loading

### Before
- `style.css` had `@import url(Google Fonts)` — render-blocking
- `index.php` head had a second `<link>` to same fonts — **double load**
- Total: 2 font requests on every page load

### After
- Single `@import` in `style.css` only
- Duplicate `<link>` removed from `index.php`, `onboarding.php`

---

## Mobile Modal UI Fix

- Image height: `220px → 160px` (saves ~60px, more content visible)
- Title font: `1.8rem → 1.15rem` (no more title cutoff on small screens)
- Prompt text box: better height + font size
- Like button: full width on mobile
- Close button: tighter positioning

---

## Pages Updated This Session

| Page | Changes |
|------|---------|
| `index.php` | Filmstrip removed, Pinterest bg removed, duplicate font removed |
| `login.php` | Filmstrip removed, aurora added, Boxicons removed |
| `onboarding.php` | Aurora added, Boxicons removed, duplicate font removed |
| `disclaimer.php` | Filmstrip removed, aurora added, Boxicons removed |
| `terms.php` | Filmstrip removed, aurora added, Boxicons removed |
| `privacy.php` | Filmstrip removed, aurora added |
| `style.css` | Wallpaper system, --bg-color semi-transparent, header opaque, mobile modal fix |
| `sitemap.php` | lastmod dates updated for all changed pages |

---

## Files Deleted

- `landingpics/lan1.webp` through `lan17.webp` (except `lan9.webp`)
- Deleted from both local and Hostinger mirror

**`lan9.webp` kept** — used as OG/Twitter card image on all pages.

---

## Remaining Opportunities (Future)

| Opportunity | Estimated Savings | Effort |
|-------------|-------------------|--------|
| Minify `style.css` (4000+ lines) | ~30–40% CSS size | Medium |
| Minify `script.js` | ~25–35% JS size | Medium |
| Convert profile/upload images to WebP | Variable | Low per image |
| Lazy load all non-critical images | FCP improvement | Low |
| Add Cache-Control headers on Hostinger | Repeat visitor speed | Low |

---

## External Requests (Current State)

| Resource | Required? | Notes |
|----------|-----------|-------|
| Google Fonts (Outfit, Lora, Playfair) | Yes | Loaded via style.css @import |
| Font Awesome CDN | Yes | Async loaded, non-blocking |
| Google Analytics (gtag.js) | Yes | `async` — non-blocking |
| Firebase Auth | Yes | Only on login page |

**Pinterest, Boxicons, Vanta.js, Three.js, particles.js — ALL REMOVED ✅**
