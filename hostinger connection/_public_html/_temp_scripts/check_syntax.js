const fs = require('fs');
const c = fs.readFileSync('secret_code.php','utf8');
const lines = c.split('\n');
lines.forEach((l,i) => {
    if(l.includes("':?>") || l.includes("':)") || l.includes("??,") || l.includes("??)")) {
        console.log((i+1)+':', l.trim());
    }
});
