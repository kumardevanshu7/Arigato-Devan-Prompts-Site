const fs = require('fs');

const files = [
    'analytics.php',
    'analytics_fixed.php',
    'hostinger connection/_public_html/analytics.php',
    'hostinger connection/_public_html/analytics_fixed.php',
];

files.forEach(f => {
    try {
        let c = fs.readFileSync(f, 'utf8');
        // "&mdash;" is the garbled em dash —
        const fixed = c.replace(/&mdash;/g, '&mdash;');
        if (fixed !== c) {
            fs.writeFileSync(f, fixed, 'utf8');
            console.log('✅ Fixed:', f);
        } else {
            console.log('—', f, '(no match, checking raw...)');
            // Try reading as binary
            const cb = fs.readFileSync(f, 'latin1');
            // â€" in latin1 is the UTF-8 bytes for em dash: E2 80 94
            const fixedb = cb.replace(/&mdash;/g, '&mdash;');
            if (fixedb !== cb) {
                fs.writeFileSync(f, fixedb, 'latin1');
                console.log('✅ Fixed (binary):', f);
            }
        }
    } catch(e) { console.log('⚠ Skip:', f); }
});
