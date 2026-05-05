const fs = require('fs');

// Fix upload_prompt.php
try {
    let content = fs.readFileSync('upload_prompt.php', 'utf8');
    content = content.replace(/<i class="bx bx-lock-alt type-icon"></i>/g, '<i class="bx bx-lock-alt type-icon"></i>');
    content = content.replace(/<i class="bx bx-moon type-icon"></i>/g, '<i class="bx bx-moon type-icon"></i>');
    content = content.replace(/<i class="bx bxs-hot type-icon"></i>/g, '<i class="bx bxs-hot type-icon"></i>');
    fs.writeFileSync('upload_prompt.php', content, 'utf8');
    console.log('✅ Fixed upload_prompt.php');
} catch(e) {}

// Fix index.php
try {
    let content = fs.readFileSync('index.php', 'utf8');
    content = content.replace(/→/g, '→');
    fs.writeFileSync('index.php', content, 'utf8');
    console.log('✅ Fixed index.php');
} catch(e) {}

// Fix dashboard.php
try {
    let content = fs.readFileSync('dashboard.php', 'utf8');
    content = content.replace(/&mdash;/g, '&mdash;');
    fs.writeFileSync('dashboard.php', content, 'utf8');
    console.log('✅ Fixed dashboard.php');
} catch(e) {}
