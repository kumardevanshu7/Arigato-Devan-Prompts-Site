const fs = require('fs');
const path = require('path');

// Boxicons CDN line to inject
const BOXICONS_CDN = `<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>`;
const FA_CDN_PATTERN = /(<link[^>]*font-awesome[^>]*>)/i;

const phpFiles = [];

function walk(dir) {
    fs.readdirSync(dir).forEach(f => {
        if (f.startsWith('.') || f === 'node_modules' || f === '_temp_scripts' || f === 'hostinger connection') return;
        const p = path.join(dir, f);
        if (fs.statSync(p).isDirectory()) walk(p);
        else if (f.endsWith('.php')) phpFiles.push(p);
    });
}

walk('.');

let fixed = 0;
phpFiles.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;

    // 1. Fix garbled mojibake emoji patterns in type-icon spans
    const mojibakePatterns = [
        // 🔒 (lock)
        { from: /\uD83C\uDF89(?!\w)/g, to: `<i class='bx bx-lock-alt'></i>` },
        // 🌙 (moon)
        { from: /<i class="bx bx-moon type-icon"></i>/g, to: `<i class='bx bx-moon'></i>` },
        // 🔥 (fire/hot)
        { from: /\uD83D\uDD25/g, to: `<i class='bx bxs-hot'></i>` },
        // ⭐ star
        { from: /ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ/g, to: `<i class='bx bxs-star'></i>` },
        // 🔑 key
        { from: /<i class='bx bx-key'></i>/g, to: `<i class='bx bx-key'></i>` },
        // ⚠️ warning in alerts
        { from: /ÃƒÂ¢Ã…Â¡Ã‚Â ÃƒÂ¯Ã‚Â¸Ã‚Â/g, to: `⚠️` },
        // — dash  
        { from: /ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â/g, to: `—` },
    ];

    mojibakePatterns.forEach(({ from, to }) => {
        if (from.test(content)) {
            content = content.replace(from, to);
            changed = true;
        }
    });

    // 2. Add Boxicons CDN after FontAwesome CDN (only if FA CDN present and Boxicons not already added)
    if (content.includes('font-awesome') && !content.includes('boxicons')) {
        content = content.replace(FA_CDN_PATTERN, `$1\n${BOXICONS_CDN}`);
        changed = true;
    }
    // Also add if no FA but has <head>
    else if (!content.includes('boxicons') && content.includes('</head>')) {
        content = content.replace('</head>', `${BOXICONS_CDN}\n</head>`);
        changed = true;
    }

    if (changed) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('✅ Fixed:', path.basename(file));
        fixed++;
    }
});

console.log(`\nDone! Fixed ${fixed} files.`);
