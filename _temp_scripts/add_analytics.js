const fs = require('fs');
const path = require('path');

const directories = [
    'c:\\xampp\\htdocs\\Arigato Development Site',
    'c:\\xampp\\htdocs\\Arigato Development Site\\hostinger connection\\_public_html'
];

directories.forEach(dir => {
    // Check if gtag.php exists in root, if not, copy it from hostinger connection
    const sourceGtag = 'c:\\xampp\\htdocs\\Arigato Development Site\\hostinger connection\\_public_html\\gtag.php';
    const targetGtag = path.join(dir, 'gtag.php');
    if (!fs.existsSync(targetGtag)) {
        if (fs.existsSync(sourceGtag)) {
            fs.copyFileSync(sourceGtag, targetGtag);
            console.log(`Copied gtag.php to ${dir}`);
        } else {
            console.log(`Source gtag.php not found at ${sourceGtag}`);
        }
    }

    const files = fs.readdirSync(dir);
    let count = 0;
    
    for (const file of files) {
        if (!file.endsWith('.php') || file === 'gtag.php') continue;
        
        const filePath = path.join(dir, file);
        let content = fs.readFileSync(filePath, 'utf8');
        
        // If file contains </head> and doesn't already have gtag.php included
        if (content.includes('</head>')) {
            if (!content.includes("include_once 'gtag.php'") && !content.includes('include "gtag.php"')) {
                content = content.replace('</head>', "    <?php include_once 'gtag.php'; ?>\n</head>");
                fs.writeFileSync(filePath, content);
                console.log(`Added analytics to ${filePath}`);
                count++;
            }
        }
    }
    console.log(`Updated ${count} files in ${dir}`);
});
