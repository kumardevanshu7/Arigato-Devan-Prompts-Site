const fs = require('fs');
const path = require('path');

const BOXICONS_CDN = `<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>`;
const FA_CDN_PATTERN = /(<link[^>]*font-awesome[^>]*>)/i;

const phpFiles = [];

function walk(dir) {
    fs.readdirSync(dir).forEach(f => {
        if (f.startsWith('.') || f === '_temp_scripts') return;
        const p = path.join(dir, f);
        if (fs.statSync(p).isDirectory()) walk(p);
        else if (f.endsWith('.php')) phpFiles.push(p);
    });
}

walk('hostinger connection/_public_html');

let fixed = 0;
phpFiles.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;

    const mojibakePatterns = [
        { from: /ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â(?!\w)/g, to: `<i class='bx bx-lock-alt'></i>` },
        { from: /ÃƒÂ°Ã…Â¸Ã…â€™Ã¢â€žÂ¢/g, to: `<i class='bx bx-moon'></i>` },
        { from: /ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â¥/g, to: `<i class='bx bxs-hot'></i>` },
        { from: /ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ/g, to: `<i class='bx bxs-star'></i>` },
        { from: /ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬Ëœ/g, to: `<i class='bx bx-key'></i>` },
        { from: /ÃƒÂ¢Ã…Â¡Ã‚Â ÃƒÂ¯Ã‚Â¸Ã‚Â/g, to: `⚠️` },
        { from: /ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â/g, to: `—` },
    ];

    mojibakePatterns.forEach(({ from, to }) => {
        if (from.test(content)) {
            content = content.replace(from, to);
            changed = true;
        }
    });

    if (content.includes('font-awesome') && !content.includes('boxicons')) {
        content = content.replace(FA_CDN_PATTERN, `$1\n${BOXICONS_CDN}`);
        changed = true;
    } else if (!content.includes('boxicons') && content.includes('</head>')) {
        content = content.replace('</head>', `${BOXICONS_CDN}\n</head>`);
        changed = true;
    }

    if (changed) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('✅ Fixed:', path.basename(file));
        fixed++;
    }
});

console.log(`\nDone! Fixed ${fixed} hostinger files.`);
