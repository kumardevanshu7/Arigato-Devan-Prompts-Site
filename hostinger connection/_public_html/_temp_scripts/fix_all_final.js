const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    try {
        fs.readdirSync(dir).forEach(f => {
            if (f.startsWith('.') || f === '_temp_scripts' || f === 'node_modules') return;
            const p = path.join(dir, f);
            if (fs.statSync(p).isDirectory()) results = results.concat(walk(p));
            else if (f.endsWith('.php')) results.push(p);
        });
    } catch(e) {}
    return results;
}

const files = walk('.');

// Comprehensive line-by-line context-aware replacement
const fixes = [
    // Non-breaking space trailing after FA arrow icon (most common)
    { from: / /g, to: ' ' },
    { from: /$/gm, to: '' },

    // Middle dot ·
    { from: /&middot;/g, to: '&middot;' },

    // Em dash – in titles/comments
    { from: /&ndash;(?= )/g, to: '&ndash;' },
    { from: /&ndash; "/g, to: '&ndash; "' },
    { from: /&ndash;/g, to: '&ndash;' },

    // Ellipsis …
    { from: /&ndash;¦/g, to: '&hellip;' },

    // 🔑 key icon in labels → Boxicon
    { from: /<i class='bx bx-key'></i>/g, to: '<i class=\'bx bx-key\'></i>' },

    // 🔥 fire in JS strings → keep as is or use unicode escape  
    { from: /\uD83D\uDD25/g, to: '\\uD83D\\uDD25' },

    // ✡ star of david or similar in JS
    { from: /\u2721/g, to: '\\u2721' },

    // 💥 collision in JS  
    { from: /\uD83D\uDCA5/g, to: '\\uD83D\\uDCA5' },

    // 🎉 party in JS
    { from: /\uD83C\uDF89/g, to: '\\uD83C\\uDF89' },

    // ═ double horizontal line in CSS comments (box drawing)
    { from: /ÃƒÆ'Ã‚Â¢&ndash;ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬/g, to: '=' },

    // 🌟 glowing star in progress.php labels
    { from: /\uD83C\uDF1F/g, to: '\\uD83C\\uDF1F' },
    { from: /ÃƒÂ°Ã…Â¸/g, to: '' }, // catch-all for remaining emoji garble

    // ⚡ lightning in strings
    { from: /ÃƒÂ¢Ã…Â Ã‚Â¡/g, to: '\\u26A1' },

    // Breadcrumb title garble in blog_edit.php  
    { from: //g, to: '' },

    // Any remaining Ãƒâ€šÃ‚ sequences
    { from: //g, to: '' },
    { from: /ÃƒÆ'Ã‚Â/g, to: '' },
];

let totalFixed = 0;

files.forEach(filePath => {
    try {
        let content = fs.readFileSync(filePath, 'utf8');
        let changed = false;

        fixes.forEach(({ from, to }) => {
            const before = content;
            content = content.replace(from, to);
            if (content !== before) changed = true;
            if (from.flags && from.flags.includes('g')) from.lastIndex = 0;
        });

        if (changed) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log('✅ Fixed:', path.relative('.', filePath));
            totalFixed++;
        }
    } catch(e) {
        console.log('⚠ Error:', filePath, e.message);
    }
});

console.log(`\nDone! Fixed ${totalFixed} files.`);
