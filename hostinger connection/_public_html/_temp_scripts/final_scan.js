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

// Only check main folder (not hostinger - it's a copy)
const files = walk('.').filter(f => !f.includes('hostinger connection'));

let issues = 0;
files.forEach(filePath => {
    const content = fs.readFileSync(filePath, 'utf8');
    // Check for common garbled patterns
    const matches = content.match(/Ãf|Ãƒâ€š|ÃƒÂ°|ÃƒÂ¢Ã¢â€šÂ¬|Ã†â€™|Ãƒâ€šÃ‚/g);
    if (matches) {
        console.log(`❌ ${path.basename(filePath)}: ${matches.length} garbled sequences`);
        // Show which lines
        content.split('\n').forEach((l, i) => {
            if (/Ãf|Ãƒâ€š|ÃƒÂ°|ÃƒÂ¢Ã¢â€šÂ¬|Ã†â€™|Ãƒâ€šÃ‚/.test(l)) {
                console.log(`   Line ${i+1}: ${l.trim().substring(0, 80)}`);
            }
        });
        issues++;
    }
});

if (issues === 0) {
    console.log('✅ ALL CLEAN! Zero garbled characters found across the entire site.');
} else {
    console.log(`\n⚠ Found issues in ${issues} files.`);
}
