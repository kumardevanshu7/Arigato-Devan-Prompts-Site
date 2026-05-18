const fs = require('fs');
function fixFile(path) {
    if (!fs.existsSync(path)) return;
    let buf = fs.readFileSync(path);
    // Remove NUL bytes (0x00)
    let cleanBuf = Buffer.alloc(buf.length);
    let j = 0;
    for(let i = 0; i < buf.length; i++) {
        if(buf[i] !== 0x00) {
            cleanBuf[j++] = buf[i];
        }
    }
    cleanBuf = cleanBuf.slice(0, j);
    let str = cleanBuf.toString('utf8');
    // Remove the trailing corrupted text
    str = str.replace(/\/ \*   S a v e d   P r o m p t s   L a y o u t   \* \//g, '');
    str = str.replace(/\/\* Saved Prompts Layout \*\//g, ''); // also remove standard text if it got mixed
    // Trim any trailing whitespace/newlines
    str = str.trim() + '\n';
    fs.writeFileSync(path, str, 'utf8');
    console.log('Fixed ' + path);
}

fixFile('style.css');
fixFile('hostinger connection/_public_html/style.css');
