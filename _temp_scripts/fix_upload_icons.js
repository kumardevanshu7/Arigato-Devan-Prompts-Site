const fs = require('fs');
const path = require('path');

const BOXICONS_CDN = `<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>`;

// Read file as binary buffer, convert garbled byte sequences to Boxicon tags
// The original emojis were stored as raw UTF-8 but then double-encoded.
// We identify them by their exact binary fingerprint in the type-icon spans.

function fixFile(filePath) {
    // Read as binary (latin1 = 1:1 byte mapping)
    let content = fs.readFileSync(filePath, 'latin1');
    let changed = false;

    // Each garbled sequence — identified from actual file bytes
    // 🔒 lock: original UTF-8 bytes = F0 9F 94 92 — double-encoded garble
    const lock_garble = '\xC3\x83\xC6\x92\xC3\x82\xB0\xC3\x83\xE2\x80\xA6\xC3\x82\xB8\xC3\x82\xA2\xC3\x83\xE2\x82\xAC\xC3\x82\xC3\xC6\x92\xC3\x82';
    // 🔥 fire: F0 9F 94 A5
    const fire_garble = '\xC3\x83\xC6\x92\xC3\x82\xB0\xC3\x83\xE2\x80\xA6\xC3\x82\xB8\xC3\x82\xA2\xC3\x83\xE2\x82\xAC\xC3\x82\xC3\xC6\x92\xC3\x82\xA5';
    // 🌙 moon: F0 9F 8C 99
    const moon_garble = '\xC3\x83\xC6\x92\xC3\x82\xB0\xC3\x83\xE2\x80\xA6\xC3\x82\xB8\xC3\x83\xE2\x80\xA6\xE2\x80\x99\xC3\x83\xE2\x82\xAC\xE2\x84\xA2';

    const lock_icon = `<i class='bx bx-lock-alt'></i>`;
    const fire_icon = `<i class='bx bxs-hot'></i>`;
    const moon_icon = `<i class='bx bx-moon'></i>`;

    if (content.includes(fire_garble)) {
        content = content.split(fire_garble).join(fire_icon);
        changed = true;
    }
    if (content.includes(lock_garble)) {
        content = content.split(lock_garble).join(lock_icon);
        changed = true;
    }
    if (content.includes(moon_garble)) {
        content = content.split(moon_garble).join(moon_icon);
        changed = true;
    }

    // Add Boxicons CDN if missing (write back as latin1 to preserve bytes)
    if (!content.includes('boxicons') && content.includes('font-awesome')) {
        content = content.replace(/(<link[^>]*font-awesome[^>]*>)/i, `$1\n${BOXICONS_CDN}`);
        changed = true;
    }

    if (changed) {
        fs.writeFileSync(filePath, content, 'latin1');
        console.log('✅ Fixed:', path.basename(filePath));
    }
    return changed;
}

// Run on upload_prompt.php and hostinger copy
const targets = [
    'upload_prompt.php',
    'hostinger connection/_public_html/upload_prompt.php'
];

targets.forEach(f => {
    try { fixFile(f); } catch(e) { console.error('Error on', f, e.message); }
});
console.log('Done.');
