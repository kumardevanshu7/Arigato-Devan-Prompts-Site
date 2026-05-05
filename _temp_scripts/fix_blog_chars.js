const fs = require('fs');
const path = require('path');

const files = [
    'blog.php',
    'blog_admin.php',
    'blogs.php',
    'blog_create.php',
    'blog_edit.php',
    'hostinger connection/_public_html/blog.php',
    'hostinger connection/_public_html/blog_admin.php',
    'hostinger connection/_public_html/blogs.php',
    'hostinger connection/_public_html/blog_create.php',
    'hostinger connection/_public_html/blog_edit.php',
];

// Patterns to fix - ordered from most specific to least
const fixes = [
    // Middle dot separator (·)
    { from: /ÃƒâšÃ‚Â·/g, to: '&middot;' },
    { from: /&middot;/g, to: '&middot;' },
    { from: /Ã†â€™Ã‚Â·/g, to: '&middot;' },

    // Non-breaking space artifact after arrow icon (← Â Back to Blogs → ← Back to Blogs)
    { from: /ÃƒâšÃ‚Â /g, to: ' ' },
    { from: / /g, to: ' ' },

    // Em dash in title (–)
    { from: /&ndash;/g, to: '&ndash;' },

    // Catch-all for any remaining Ãf patterns that are non-breaking spaces
    { from: /&ndash;·/g, to: '&middot;' },
];

let totalFixed = 0;

files.forEach(filePath => {
    try {
        let content = fs.readFileSync(filePath, 'utf8');
        let changed = false;

        fixes.forEach(({ from, to }) => {
            if (from.test(content)) {
                content = content.replace(from, to);
                changed = true;
                from.lastIndex = 0;
            }
        });

        if (changed) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log('✅ Fixed:', path.basename(filePath));
            totalFixed++;
        } else {
            console.log('— No change:', path.basename(filePath));
        }
    } catch(e) {
        console.log('⚠ Skip:', filePath, '-', e.message);
    }
});

console.log(`\nDone! Fixed ${totalFixed} files.`);
