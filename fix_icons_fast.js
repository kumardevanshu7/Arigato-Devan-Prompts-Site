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
        content = content.replace(/\u00c2\u00a0/g, ' ');         // Non-breaking space (UTF-8 mojibake)
        content = content.replace(/\u00e2\u0080\u008b/g, '');     // Zero-width space
        content = content.replace(/&middot;/g, '&middot;');        // Middle dot (already correct)
        content = content.replace(/\u00e2\u0080\u0093/g, '&ndash;'); // En dash mojibake
        content = content.replace(/\u00e2\u0080\u0094/g, '&mdash;'); // Em dash mojibake
        content = content.replace(/\u00e2\u0080\u00a6/g, '&hellip;'); // Ellipsis mojibake

        // 3. Icon replacements — replace garbled mojibake with proper Boxicon markup
        // Lock icon (was garbled bx-key)
        content = content.replace(/<i class='bx bx-key'><\/i>/g, "<i class='bx bx-key'></i>");
        // Fire emoji mojibake -> boxicon
        content = content.replace(/\uD83D\uDD25/g, "<i class='bx bxs-hot'></i>");
        // Star of David mojibake -> boxicon star
        content = content.replace(/\u2721/g, "<i class='bx bxs-star'></i>");
        // Collision emoji mojibake -> boxicon
        content = content.replace(/\uD83D\uDCA5/g, "<i class='bx bx-error-circle'></i>");
        // Party popper emoji mojibake -> boxicon
        content = content.replace(/\uD83C\uDF89/g, "<i class='bx bx-party'></i>");
        // Glowing star emoji mojibake -> boxicon
        content = content.replace(/\uD83C\uDF1F/g, "<i class='bx bxs-star'></i>");

        // upload_prompt.php prompt types specific fixes (Lock, Moon, Hot)
        if (filePath.includes('upload_prompt.php')) {
            content = content.replace(/<span class="type-icon"><i class='bx bx-key'><\/i><\/span>/g, '<i class="bx bx-lock-alt type-icon"></i>');
            content = content.replace(/<span class="type-icon">[\uD83C\uDF19\u{1F319}]<\/span>/gu, '<i class="bx bx-moon type-icon"></i>');
            content = content.replace(/<span class="type-icon">[\uD83D\uDD25\u{1F525}]<\/span>/gu, '<i class="bx bxs-hot type-icon"></i>');
        }

        if (content !== originalContent) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log('✅ Updated:', filePath);
        }

    } catch (e) {
        console.error('Error with', filePath, e.message);
    }
});

console.log('Finished Icon and Garbled Text fixes!');
