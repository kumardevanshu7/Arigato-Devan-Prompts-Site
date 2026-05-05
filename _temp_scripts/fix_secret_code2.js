const fs = require('fs');
const files = [
    'secret_code.php',
    'hostinger connection/_public_html/secret_code.php'
];

files.forEach(f => {
    let c = fs.readFileSync(f, 'utf8');
    c = c.replace(/':\?>/g, "':'';?>");
    c = c.replace(/':\)/g, "':'')");
    c = c.replace(/\?\?,/g, "??'',");
    c = c.replace(/\?\?\)/g, "??'')");
    c = c.replace(/':\?>/g, "':'';?>"); // Just in case
    
    // specifically target: '":?>' which might be '":'';?>'
    c = c.replace(/":\?>/g, "\":'';?>");
    
    fs.writeFileSync(f, c, 'utf8');
    console.log('Fixed', f);
});
