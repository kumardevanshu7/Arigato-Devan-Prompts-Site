const fs = require('fs');
const path = require('path');

function walk(d) {
    let r = [];
    try {
        fs.readdirSync(d).forEach(f => {
            if (f.startsWith('.') || f === 'node_modules' || f.includes('hostinger')) return;
            let p = path.join(d, f);
            if (fs.statSync(p).isDirectory()) r = r.concat(walk(p));
            else if (f.endsWith('.php')) r.push(p);
        });
    } catch(e) {}
    return r;
}

const files = walk('.');

files.forEach(f => {
    try {
        let c = fs.readFileSync(f, 'utf8');
        let o = c;

        c = c.replace(/ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â /g, '&mdash;');
        c = c.replace(/ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ ÃƒÆ’Ã‚Â¯¸ /g, '<i class="fa-solid fa-pen"></i> ');
        c = c.replace(/ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“/g, '✓');
        c = c.replace(/ÃƒÂ¢Ã…Â¡Ã‚Â ÃƒÂ¯Ã‚Â¸Ã‚Â /g, '⚠️');
        c = c.replace(/ÃƒÂ°Ã…Â¸Ã…Â¡Ã¢â€šÂ¬/g, '🚀');
        c = c.replace(/ÃƒÆ’Ã¢â‚¬Å¡&middot;/g, '&middot;');

        if (c !== o) {
            fs.writeFileSync(f, c, 'utf8');
            console.log('Fixed', f);
        }
    } catch(e) {}
});
