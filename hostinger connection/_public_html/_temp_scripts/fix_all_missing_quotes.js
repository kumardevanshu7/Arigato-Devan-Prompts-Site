const fs = require('fs');
const path = require('path');

function fix(f) {
    try {
        let c = fs.readFileSync(f, 'utf8');
        let orig = c;
        c = c.replace(/':\?>/g, "':'';?>");
        c = c.replace(/':\)/g, "':'')");
        c = c.replace(/\?\?,/g, "??'',");
        c = c.replace(/\?\?\)/g, "??'')");
        c = c.replace(/":\?>/g, "\":'';?>");
        
        if (c !== orig) {
            fs.writeFileSync(f, c, 'utf8');
            console.log('Fixed syntax in:', f);
        }
    } catch(e) {}
}

function walk(d) {
    try {
        fs.readdirSync(d).forEach(f => {
            if(f.startsWith('.') || f.includes('node_modules')) return;
            const p = path.join(d, f);
            try {
                if(fs.statSync(p).isDirectory()) walk(p);
                else if(f.endsWith('.php')) fix(p);
            } catch(e) {}
        });
    } catch(e) {}
}

walk('.');
