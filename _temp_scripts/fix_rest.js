const fs = require('fs');

function r(f, regex, repl) {
    try {
        let c = fs.readFileSync(f, 'utf8');
        let o = c;
        c = c.replace(regex, repl);
        if (c !== o) {
            fs.writeFileSync(f, c, 'utf8');
            console.log('Fixed', f);
        }
    } catch(e) {}
}

// Custom manual replacements
r('blog_edit.php', /<div class="bc-title">Ã[^\<]+ Edit Blog<\/div>/, '<div class="bc-title"><i class="fa-solid fa-pen"></i> Edit Blog</div>');
r('upload_prompt.php', /alert\('Ã[^ ]+ /, 'alert(\'⚠️ ');
r('profile.php', /\${len}\/15 Ã[^`]+`/, '${len}/15 ✓`');
r('progress.php', /1 Million Views Ã[^']+/, '1 Million Views 🚀');
r('progress.php', /10K Achieved [^']+/, '10K Achieved 🌟');

// General em-dash replacements for the rest of the files
const files = ['insta_viral.php', 'gallery.php', 'edit_prompt.php', 'manage_prompts.php', 'onboarding.php', 'unreleased.php', 'blogs.php', 'progress.php', 'upload_prompt.php'];

files.forEach(f => {
    try {
        let c = fs.readFileSync(f, 'utf8');
        let o = c;
        c = c.replace(/Ã[^\x00-\x7F]+/g, '&mdash;');
        if (c !== o) {
            fs.writeFileSync(f, c, 'utf8');
            console.log('Fixed dashes in', f);
        }
    } catch(e) {}
});
