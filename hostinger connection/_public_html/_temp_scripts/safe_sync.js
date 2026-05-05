const fs = require('fs');
const path = require('path');

// ⚠️ NEVER sync these files - they have environment-specific configs
const PROTECTED_FILES = [
    'db.php',
    'firebase_auth.php',
    'google_config.php',
    'secret_code.php',
];

const src = '.';
const dst = 'hostinger connection/_public_html';

const files = fs.readdirSync(src).filter(f => {
    if (PROTECTED_FILES.includes(f)) {
        console.log(`🔒 PROTECTED (skipped): ${f}`);
        return false;
    }
    if (!f.match(/\.(php|js|css)$/)) return false;
    if (f.startsWith('.')) return false;
    return true;
});

files.forEach(f => {
    fs.copyFileSync(path.join(src, f), path.join(dst, f));
    console.log(`✅ Synced: ${f}`);
});

console.log(`\nDone! Synced ${files.length} files. Protected files were NOT touched.`);
