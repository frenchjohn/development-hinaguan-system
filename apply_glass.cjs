const fs = require('fs');
const path = require('path');

const cssDir = path.join(__dirname, 'resources', 'css', 'staff_css');
const files = fs.readdirSync(cssDir).filter(f => f.startsWith('staff_') && f.endsWith('.css'));

files.forEach(file => {
    const filePath = path.join(cssDir, file);
    let content = fs.readFileSync(filePath, 'utf8');

    // Replace Light Mode Card Backgrounds
    content = content.replace(/background:\s*#fff(?:fff)?;/g, 
        'background: var(--glass-bg);\n    backdrop-filter: var(--glass-blur);\n    -webkit-backdrop-filter: var(--glass-blur);');
    
    // Replace dark mode backgrounds (common ones seen)
    content = content.replace(/background:\s*#(?:1a2e1d|1a3d1e|1c2b22);/g, 
        'background: var(--glass-bg);');

    // Replace typical light mode borders to glass borders in cards
    content = content.replace(/border(-color)?:\s*1px\s+solid\s+(rgba[^;]+|#[a-fA-F0-9]{3,6}|var\(--border\));/g, 
        'border$1: 1px solid var(--glass-border);');
    
    // Replace typical box shadows to glass shadow
    content = content.replace(/box-shadow:\s*0\s+[^;]+;/g, 
        'box-shadow: var(--glass-shadow);');

    fs.writeFileSync(filePath, content, 'utf8');
    console.log('Glassified', file);
});
