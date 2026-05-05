const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    try {
        fs.readdirSync(dir).forEach(f => {
            if (f.startsWith('.') || f === 'node_modules' || f.includes('hostinger')) return;
            const p = path.join(dir, f);
            if (fs.statSync(p).isDirectory()) results = results.concat(walk(p));
            else if (f.endsWith('.php') || f.endsWith('.js')) results.push(p);
        });
    } catch(e) {}
    return results;
}

const files = walk('.');

const boxiconsCDN = `    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>\n`;

files.forEach(filePath => {
    try {
        let content = fs.readFileSync(filePath, 'utf8');
        let originalContent = content;

        // 1. Inject Boxicons CDN if not present and if it's a PHP file with a <head> or <link
        if (filePath.endsWith('.php') && !content.includes('boxicons.min.css')) {
            // Find a good place to inject: right before </head> or after FontAwesome
            if (content.includes('font-awesome')) {
                content = content.replace(/(<link[^>]*font-awesome[^>]*>)/i, '$1\n' + boxiconsCDN);
            } else if (content.includes('</head>')) {
                content = content.replace('</head>', boxiconsCDN + '</head>');
            } else if (content.includes('style.css')) {
                 content = content.replace(/(<link[^>]*style\.css[^>]*>)/i, '$1\n' + boxiconsCDN);
            }
        }

        // 2. Fix Garbled text / Mojibake
        content = content.replace(/ /g, ' '); // Trailing non-breaking space
        content = content.replace(/$/gm, '');
        content = content.replace(/&middot;/g, '&middot;'); // Middle dot
        content = content.replace(/&ndash;(?= )/g, '&ndash;'); // Em dash
        content = content.replace(/&ndash; "/g, '&ndash; "');
        content = content.replace(/&ndash;/g, '&ndash;');
        content = content.replace(/&mdash;/g, '&mdash;'); // Em dash in analytics
        content = content.replace(/&ndash;¦/g, '&hellip;'); // Ellipsis

        // 3. Icon replacements
        content = content.replace(/<i class='bx bx-key'></i>/g, "<i class='bx bx-key'></i>"); // Key
        content = content.replace(/\uD83D\uDD25/g, '\\uD83D\\uDD25'); // Fire
        content = content.replace(/\u2721/g, '\\u2721'); // Star
        content = content.replace(/\uD83D\uDCA5/g, '\\uD83D\\uDCA5'); // Collision
        content = content.replace(/\uD83C\uDF89/g, '\\uD83C\\uDF89'); // Party
        content = content.replace(/\uD83C\uDF1F/g, '\\uD83C\\uDF1F'); // Glowing star
        
        // upload_prompt.php prompt types specific fixes (Lock, Moon, Hot)
        if (filePath.includes('upload_prompt.php')) {
            content = content.replace(/<span class="type-icon"><i class='bx bx-key'></i><\/span>/g, '<i class="bx bx-lock-alt type-icon"></i>');
            content = content.replace(/<span class="type-icon">ÃƒÂ°Ã…Â¸Ã…â€™Ã¢â‚¬â„¢<\/span>/g, '<i class="bx bx-moon type-icon"></i>');
            content = content.replace(/<span class="type-icon">\uD83D\uDD25<\/span>/g, '<i class="bx bxs-hot type-icon"></i>');
        }

        // Catch-alls
        content = content.replace(//g, ''); 
        content = content.replace(//g, ''); 

        if (content !== originalContent) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log('✅ Updated:', filePath);
        }

    } catch (e) {
        console.error('Error with', filePath, e.message);
    }
});

console.log('Finished Icon and Garbled Text fixes!');
