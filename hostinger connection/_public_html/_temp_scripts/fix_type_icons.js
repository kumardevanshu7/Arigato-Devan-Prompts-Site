const fs = require('fs');

const targets = [
    'upload_prompt.php',
    'hostinger connection/_public_html/upload_prompt.php'
];

targets.forEach(filePath => {
    const lines = fs.readFileSync(filePath, 'utf8').split('\n');
    let changed = false;

    lines.forEach((line, i) => {
        // Line with garbled Secret Code icon (🔒) — replace entire span content
        if (line.includes('<span class="type-icon">') && !line.includes('bx ') && !line.includes('bxs-') && !line.includes('fa-')) {
            const lineNum = i + 1;
            const indent = line.match(/^(\s*)/)[1];

            // Detect which icon based on surrounding context
            // Check previous few lines to figure out which type-card this is
            const context = lines.slice(Math.max(0, i-5), i).join('\n');
            
            if (context.includes('value="secret"')) {
                lines[i] = `${indent}<span class="type-icon"><i class='bx bx-lock-alt'></i></span>`;
                console.log(`Line ${lineNum}: Fixed Secret Code icon`);
                changed = true;
            } else if (context.includes('value="unreleased"')) {
                lines[i] = `${indent}<span class="type-icon"><i class='bx bx-moon'></i></span>`;
                console.log(`Line ${lineNum}: Fixed Unreleased icon`);
                changed = true;
            } else if (context.includes('value="insta_viral"')) {
                lines[i] = `${indent}<span class="type-icon"><i class='bx bxs-hot'></i></span>`;
                console.log(`Line ${lineNum}: Fixed Insta Viral icon`);
                changed = true;
            }
        }
    });

    if (changed) {
        fs.writeFileSync(filePath, lines.join('\n'), 'utf8');
        console.log('✅ Saved:', filePath);
    }
});
