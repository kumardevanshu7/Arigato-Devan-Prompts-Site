const fs = require('fs');
const path = require('path');

function walk(d) {
    let r = [];
    fs.readdirSync(d).forEach(f => {
        if (f.startsWith('.') || f === 'node_modules' || f.includes('hostinger')) return;
        let p = path.join(d, f);
        if (fs.statSync(p).isDirectory()) r = r.concat(walk(p));
        else if (f.endsWith('.php') || f.endsWith('.js')) r.push(p);
    });
    return r;
}

const files = walk('.');

files.forEach(f => {
    try {
        let original = fs.readFileSync(f, 'utf8');
        let c = original;
        
        // Em dash replacements
        c = c.replace(/&mdash;/g, '&mdash;');
        
        // upload_prompt.php specific icons
        c = c.replace(/<i class="bx bx-lock-alt type-icon"></i>/g, '<i class="bx bx-lock-alt type-icon"></i>');
        c = c.replace(/<i class="bx bx-moon type-icon"></i>/g, '<i class="bx bx-moon type-icon"></i>');
        c = c.replace(/<i class="bx bxs-hot type-icon"></i>/g, '<i class="bx bxs-hot type-icon"></i>');
        
        // Key icon (Access code label)
        c = c.replace(/<i class="bx bx-key"></i>/g, '<i class="bx bx-key"></i>');
        
        // JS Alert icons
        c = c.replace(/⚠️/g, '⚠️');
        c = c.replace(/💥/g, '💥');
        c = c.replace(/✡/g, '✡');
        c = c.replace(/🌟/g, '🌟');
        
        // Arrow in index.php Explore Prompts
        c = c.replace(/→/g, '→');

        if (c !== original) {
            fs.writeFileSync(f, c, 'utf8');
            console.log('✅ Cleaned up:', f);
        }
    } catch(e) {}
});

console.log('All exact byte fixes applied!');
